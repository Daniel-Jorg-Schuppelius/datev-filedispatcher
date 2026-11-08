<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Config.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Config;

use ConfigToolkit\ConfigLoader;
use ERRORToolkit\Enums\LogType;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;

/**
 * Konfiguration aus config/config.json (Pfad per FILEDISPATCHER_CONFIG änderbar).
 * Zugangsdaten der DATEV-API lassen sich per Umgebungsvariable setzen, damit sie
 * nicht im Klartext in der Konfigurationsdatei stehen müssen.
 */
class Config {
    public const ENV_CONFIG_FILE = 'FILEDISPATCHER_CONFIG';
    public const ENV_API_URL = 'DATEV_API_URL';
    public const ENV_API_USER = 'DATEV_API_USER';
    public const ENV_API_PASSWORD = 'DATEV_API_PASSWORD';
    public const ENV_API_CA_FILE = 'DATEV_API_CA_FILE';

    private const DEFAULT_CONFIG_FILE = __DIR__ . '/../../config/config.json';

    private const DEFAULT_ZIP_MAX_ENTRIES = 1000;
    private const DEFAULT_ZIP_MAX_BYTES = 500 * 1024 * 1024;
    private const DEFAULT_ZIP_MAX_RATIO = 100.0;

    private static ?Config $instance = null;
    private ConfigLoader $configLoader;
    private ?bool $debugOverride = null; // Ermöglicht das Überschreiben in Tests

    private function __construct() {
        $this->configLoader = ConfigLoader::getInstance();
        $this->loadConfig();
    }

    /**
     * @throws RuntimeException wenn die Konfigurationsdatei fehlt oder nicht geladen werden kann
     */
    public static function getInstance(): Config {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function loadConfig(): void {
        $configFile = self::env(self::ENV_CONFIG_FILE) ?? self::DEFAULT_CONFIG_FILE;

        $configPath = realpath($configFile);
        if ($configPath === false || !is_readable($configPath)) {
            throw new RuntimeException("Konfigurationsdatei nicht gefunden oder nicht lesbar: $configFile (Vorlage: config/config.json.sample)");
        }

        try {
            $this->configLoader->loadConfigFile($configPath);
        } catch (Throwable $e) {
            throw new RuntimeException("Konfigurationsdatei konnte nicht geladen werden: $configPath ({$e->getMessage()})", 0, $e);
        }
    }

    // Getter für API-Konfiguration (Umgebungsvariablen haben Vorrang)
    public function getResourceUrl(): ?string {
        return self::env(self::ENV_API_URL) ?? $this->configLoader->get("DatevAPI", "resourceurl");
    }

    public function getUser(): ?string {
        return self::env(self::ENV_API_USER) ?? $this->configLoader->get("DatevAPI", "user");
    }

    public function getPassword(): ?string {
        return self::env(self::ENV_API_PASSWORD) ?? $this->configLoader->get("DatevAPI", "password");
    }

    public function getVerifySSL(): bool {
        return (bool) $this->configLoader->get("DatevAPI", "verifySSL", true);
    }

    /**
     * CA-Datei (PEM), der zusätzlich zum Zertifikatsspeicher vertraut wird, z. B. für ein
     * selbstsigniertes DATEVconnect-Zertifikat. null = Zertifikatsspeicher des Systems.
     */
    public function getCaFile(): ?string {
        $caFile = self::env(self::ENV_API_CA_FILE) ?? $this->configLoader->get("DatevAPI", "caFile");

        return ($caFile === null || $caFile === '') ? null : (string) $caFile;
    }

    // Getter für Logging
    public function getLogType(): LogType {
        return LogType::fromString($this->configLoader->get("Logging", "log", LogType::NULL->value));
    }

    public function getLogLevel(): string {
        return $this->configLoader->get("Logging", "level", LogLevel::INFO);
    }

    public function getLogPath(): ?string {
        return $this->configLoader->get("Logging", "path");
    }

    // Getter für Debugging (unterstützt Test-Überschreibung)
    public function isDebugEnabled(): bool {
        return $this->debugOverride ?? $this->configLoader->get("Debugging", "debug", false);
    }

    public function setDebug(bool $debug): void {
        $this->debugOverride = $debug;
    }

    // Getter für Dateipfade
    public function getInternalStorePath(): ?string {
        return $this->configLoader->get("Path", "internalStore");
    }

    // Getter für Jahreskonfiguration
    public function getPreviousYears4Internal(): ?int {
        return $this->configLoader->get("maxYears", "previousYears4Internal", 0);
    }

    public function getPreviousYearsFolderName4Internal(): ?string {
        return $this->configLoader->get("maxYears", "previousYearsFolderName4Internal");
    }

    // Grenzen für ZIP-Archive (Schutz vor ZIP-Bomben)
    public function getZipMaxEntries(): int {
        return (int) $this->configLoader->get("Zip", "maxEntries", self::DEFAULT_ZIP_MAX_ENTRIES);
    }

    public function getZipMaxBytes(): int {
        return (int) $this->configLoader->get("Zip", "maxBytes", self::DEFAULT_ZIP_MAX_BYTES);
    }

    public function getZipMaxRatio(): float {
        return (float) $this->configLoader->get("Zip", "maxRatio", self::DEFAULT_ZIP_MAX_RATIO);
    }

    // Getter für Mappings & Listen

    /** @return array<string, string>|null Kategorie ("Ordner Register") => interner Unterpfad */
    public function getDatevDMSMapping(): ?array {
        return $this->configLoader->get("DatevDMSMapping");
    }

    /** @return list<string>|null */
    public function getExcludedFolders(): ?array {
        return $this->configLoader->get("ExcludedFolders");
    }

    /** @return list<string>|null */
    public function getPerYear(): ?array {
        return $this->configLoader->get("PerYear");
    }

    /** @return list<string>|null */
    public function getPerPeriod(): ?array {
        return $this->configLoader->get("PerPeriod");
    }

    private static function env(string $name): ?string {
        $value = getenv($name);

        return ($value === false || $value === '') ? null : $value;
    }
}

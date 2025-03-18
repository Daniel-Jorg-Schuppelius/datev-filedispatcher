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
use ERRORToolkit\Factories\ConsoleLoggerFactory;
use Psr\Log\LogLevel;
use Exception;

class Config {
    private static ?Config $instance = null;
    private ConfigLoader $configLoader;
    private ?bool $debugOverride = null; // Ermöglicht das Überschreiben in Tests

    private function __construct() {
        $this->configLoader = ConfigLoader::getInstance(ConsoleLoggerFactory::getLogger());
        $this->loadConfig();
    }

    public static function getInstance(): Config {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConfigured(): bool {
        return $this->getResourceUrl() !== null
            && $this->getUser() !== null
            && $this->getPassword() !== null;
    }

    private function loadConfig(): void {
        try {
            $configPath = realpath(__DIR__ . '/../../config/config.json');
            if (!$configPath) {
                throw new Exception("Config file not found.");
            }

            $this->configLoader->loadConfigFile($configPath);
        } catch (Exception $e) {
            error_log("Config-Fehler: " . $e->getMessage());
        }
    }

    // Getter für API-Konfiguration
    public function getResourceUrl(): ?string {
        return $this->configLoader->get("DatevAPI", "resourceurl");
    }

    public function getUser(): ?string {
        return $this->configLoader->get("DatevAPI", "user");
    }

    public function getPassword(): ?string {
        return $this->configLoader->get("DatevAPI", "password");
    }

    // Getter für Logging
    public function getLogType(): LogType {
        return LogType::fromString($this->configLoader->get("Logging", "log", LogType::NULL->value));
    }

    public function getLogLevel(): string {
        return $this->configLoader->get("Logging", "level", LogLevel::DEBUG);
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

    // Getter für Mappings & Listen
    public function getDatevDMSMapping(): ?array {
        return $this->configLoader->get("DatevDMSMapping");
    }

    public function getExcludedFolders(): ?array {
        return $this->configLoader->get("ExcludedFolders");
    }

    public function getPerYear(): ?array {
        return $this->configLoader->get("PerYear");
    }

    public function getPerPeriod(): ?array {
        return $this->configLoader->get("PerPeriod");
    }

    // Getter für Tenant-IDs
    public function getTenantIDs(): ?array {
        return $this->configLoader->get("TenantIDs");
    }
}

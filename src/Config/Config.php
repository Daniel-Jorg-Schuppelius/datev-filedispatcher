<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Config.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Config;

use App\Enums\LogType;
use Psr\Log\LogLevel;

class Config {
    private static ?Config $instance = null;

    private bool $debug = false;

    private LogType $logType = LogType::NULL;
    private string $logLevel = LogLevel::DEBUG;
    private ?string $logPath = null;

    private ?string $resourceUrl = null;
    private ?string $user = null;
    private ?string $password = null;

    private ?string $internalStorePath = null;
    private ?int $previousYears4Internal = null;
    private ?string $previousYearsFolderName4Internal = null;

    private ?array $datevDMSMapping = null;
    private ?array $perYear = null;
    private ?array $perPeriod = null;

    private ?array $excludedFolders = null;

    private function __construct() {
        $this->loadConfig();
    }

    public static function getInstance(): Config {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConfigured(): bool {
        return !is_null($this->resourceUrl) && !is_null($this->user) && !is_null($this->password);
    }

    private function loadConfig(): void {
        $filePath = realpath(__DIR__ . '/config.json');

        if (!$filePath || !file_exists($filePath)) {
            error_log('Config file not found, please create one at ./src/Config/config.json');
            return;
        }

        $config = json_decode(file_get_contents($filePath), true);

        $this->setApiConfig($config['DatevAPI'] ?? []);
        $this->setPathConfig($config['Path'] ?? []);
        $this->setMaxYearsConfig($config['maxYears'] ?? []);
        $this->setLoggingConfig($config['Logging'] ?? []);
        $this->setDebugConfig($config['Debugging'] ?? []);
        $this->datevDMSMapping = $config['DatevDMSMapping'] ?? null;
        $this->perYear = $config['PerYear'] ?? null;
        $this->perPeriod = $config['PerPeriod'] ?? null;
        $this->excludedFolders = $config['ExcludedFolders'] ?? null;
    }

    private function setApiConfig(array $apiConfig): void {
        foreach ($apiConfig as $value) {
            if ($value['enabled']) {
                match ($value['key']) {
                    'resourceurl' => $this->resourceUrl = $value['value'],
                    'user' => $this->user = $value['value'],
                    'password' => $this->password = $value['value'],
                    default => null,
                };
            }
        }
    }

    private function setPathConfig(array $pathConfig): void {
        foreach ($pathConfig as $value) {
            if ($value['enabled'] && $value['key'] === 'internalStore') {
                $this->internalStorePath = $value['value'];
            }
        }
    }

    private function setMaxYearsConfig(array $maxYearsConfig): void {
        foreach ($maxYearsConfig as $value) {
            if ($value['enabled']) {
                match ($value['key']) {
                    'previousYears4Internal' => $this->previousYears4Internal = (int)$value['value'],
                    'previousYearsFolderName4Internal' => $this->previousYearsFolderName4Internal = $value['value'],
                    default => null,
                };
            }
        }
    }

    private function setLoggingConfig(array $loggingConfig): void {
        foreach ($loggingConfig as $value) {
            if ($value['enabled']) {
                match ($value['key']) {
                    'log' => $this->logType = LogType::fromString($value['value']),
                    'level' => $this->logLevel = $value['value'],
                    'path' => $this->logPath = $value['value'],
                    default => null,
                };
            }
        }
    }

    private function setDebugConfig(array $debuggingConfig): void {
        foreach ($debuggingConfig as $value) {
            if (isset($value['key']) && $value['key'] === 'debug' && $value['enabled']) {
                $this->debug = (bool)$value['value'];
                if ($this->debug)
                    $this->logLevel = LogLevel::DEBUG;
            }
        }
    }

    // Getter methods for config values
    public function getInternalStorePath(): ?string {
        return $this->internalStorePath;
    }

    public function getResourceUrl(): ?string {
        return $this->resourceUrl;
    }

    public function getUser(): ?string {
        return $this->user;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function getLogType(): LogType {
        return $this->logType;
    }

    public function getLogLevel(): string {
        return $this->logLevel;
    }

    public function getLogPath(): ?string {
        return $this->logPath;
    }

    public function getPreviousYears4Internal(): ?int {
        return $this->previousYears4Internal;
    }

    public function getPreviousYearsFolderName4Internal(): ?string {
        return $this->previousYearsFolderName4Internal;
    }

    public function getDatevDMSMapping(): ?array {
        return $this->datevDMSMapping;
    }

    public function getExcludedFolders(): ?array {
        return $this->excludedFolders;
    }

    public function getPerYear(): ?array {
        return $this->perYear;
    }

    public function getPerPeriod(): ?array {
        return $this->perPeriod;
    }

    public function isDebugEnabled(): bool {
        return $this->debug;
    }

    public function setDebug(bool $debug): void {
        $this->debug = $debug;
    }
}

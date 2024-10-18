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

class Config {
    private static ?Config $instance = null;

    private bool $debug = false;

    private ?LogType $logType = LogType::NULL;
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

    private function __construct() {
        $this->setConfig();
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

    private function setConfig(): void {
        $filePath = realpath(__DIR__ . '/../../config.json');
        if ($filePath && file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $config = json_decode($jsonContent, true);

            if (isset($config['DatevAPI']) && is_array($config['DatevAPI'])) {
                foreach ($config['DatevAPI'] as $value) {
                    if ($value['key'] === 'resourceurl') {
                        $this->resourceUrl = $value['value'];
                    }
                    if ($value['key'] === 'user') {
                        $this->user = $value['value'];
                    }
                    if ($value['key'] === 'password') {
                        $this->password = $value['value'];
                    }
                }
            }

            if (isset($config['Path']) && is_array($config['Path'])) {
                foreach ($config['Path'] as $value) {
                    if ($value['key'] === 'internalStore') {
                        $this->internalStorePath = $value['value'];
                    }
                }
            }

            if (isset($config['maxYears']) && is_array($config['maxYears'])) {
                foreach ($config['maxYears'] as $value) {
                    if ($value['key'] === 'previousYears4Internal') {
                        $this->previousYears4Internal = $value['value'];
                    }
                    if ($value['key'] === 'previousYearsFolderName4Internal') {
                        $this->previousYearsFolderName4Internal = $value['value'];
                    }
                }
            }

            if (isset($config['Logging']) && is_array($config['Logging'])) {
                foreach ($config['Logging'] as $value) {
                    if ($value['key'] === 'log') {
                        $this->logType = LogType::fromString($value['value']);
                    }
                    if ($value['key'] === 'path') {
                        $this->logPath = $value['value'];
                    }
                }
            }

            if (isset($config['Debugging']) && is_array($config['Debugging'])) {
                foreach ($config['Debugging'] as $value) {
                    if (isset($value['key']) && $value['key'] === 'debug') {
                        $this->debug = (bool) $value['value'];
                    }
                }
            }

            if (isset($config['DatevDMSMapping']) && is_array($config['DatevDMSMapping'])) {
                $this->datevDMSMapping = $config['DatevDMSMapping'];
            }

            if (isset($config['PerYear']) && is_array($config['PerYear'])) {
                $this->perYear = $config['PerYear'];
            }
            if (isset($config['PerPeriod']) && is_array($config['PerPeriod'])) {
                $this->perPeriod = $config['PerPeriod'];
            }
        } else {
            error_log('Config file not found, please create one at ../../config.json');
        }
    }

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

    public function getLogType(): ?LogType {
        return $this->logType;
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

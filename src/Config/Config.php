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

class Config {
    private static ?Config $instance = null;

    private bool $debug = false;

    private ?string $resourceUrl = null;
    private ?string $user = null;
    private ?string $password = null;

    private ?string $internalStorePath = null;

    private array $datevDMSMapping = [];
    private array $perYear = [];
    private array $perPeriod = [];

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

            if (isset($config['Debugging']) && is_array($config['Debugging'])) {
                foreach ($config['Debugging'] as $value) {
                    if (isset($value['key']) && $value['key'] === 'debug') {
                        $this->debug = (bool) $value['value'];
                    }
                }
            }

            if (isset($config['DatevDMSMapping'])) {
                $this->datevDMSMapping = $config['DatevDMSMapping'];
            }

            if (isset($config['PerYear'])) {
                $this->perYear = $config['PerYear'];
            }

            if (isset($config['PerPeriod'])) {
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

    public function getDatevDMSMapping(): array {
        return $this->datevDMSMapping;
    }

    public function getPerYear(): array {
        return $this->perYear;
    }

    public function getPerPeriod(): array {
        return $this->perPeriod;
    }

    public function isDebugEnabled(): bool {
        return $this->debug;
    }

    public function setDebug(bool $debug): void {
        $this->debug = $debug;
    }
}

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

    public bool $debug = false;

    public ?string $resourceUrl = null;
    public ?string $user = null;
    public ?string $password = null;

    public ?string $internalStorePath = null;

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
        } else {
            error_log('Config file not found, please create one at ../../config.json');
        }
    }
}

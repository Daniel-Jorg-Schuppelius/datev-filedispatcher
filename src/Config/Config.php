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
    public ?string $resourceUrl = null;
    public ?string $user = null;
    public ?string $password = null;

    public ?string $internalStorePath = null;

    public function __construct() {
        $this->setConfig();
    }

    public function isConfigured(): bool {
        return !is_null($this->resourceUrl) && !is_null($this->user) && !is_null($this->password);
    }

    private function setConfig() {
        $filePath = __DIR__ . '/../../config.json';
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $config = json_decode($jsonContent, true);

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

            foreach ($config['Path'] as $value) {
                if ($value['key'] === 'internalStore') {
                    $this->internalStorePath = $value['value'];
                }
            }
        } else {
            error_log('config file not found, please create one at ../../config.json');
        }
    }
}

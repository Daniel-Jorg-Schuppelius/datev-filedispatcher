<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Config.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Config;

class Config {
    public ?int $number = null;
    public ?string $correspondence_partner_guid = null;
    public ?string $id = null;

    public function __construct() {
        $this->setConfig();
    }

    public function isConfigured(): bool {
        return !is_null($this->number) && !is_null($this->correspondence_partner_guid) && !is_null($this->id);
    }

    private function setConfig() {
        $filePath = __DIR__ . '/../../.samples/config.json';
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $config = json_decode($jsonContent, true);

            foreach ($config['Document'] as $value) {
                if ($value['key'] === 'number') {
                    $this->number = $value['value'];
                }
                if ($value['key'] === 'correspondence_partner_guid') {
                    $this->correspondence_partner_guid = $value['value'];
                }
            }

            foreach ($config['Client'] as $value) {
                if ($value['key'] === 'id') {
                    $this->id = $value['value'];
                }
            }
        } else {
            error_log('config file not found, please create one at ../../.samples/config.json');
        }
    }
}

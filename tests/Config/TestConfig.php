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

namespace Tests\Config;

use Datev\Entities\ClientMasterData\Clients\ClientID;

class TestConfig {
    private static ?TestConfig $instance = null;

    private ?string $internalStorePath = null;

    private ?array $tenantIds = null;

    private ?int $number = null;
    private ?string $correspondence_partner_guid = null;
    private ?string $id = null;

    private function __construct() {
        $this->loadConfig();
    }

    public static function getInstance(): TestConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConfigured(): bool {
        return !is_null($this->internalStorePath) && !is_null($this->number) && !is_null($this->correspondence_partner_guid) && !is_null($this->id);
    }

    private function loadConfig(): void {
        $filePath = realpath(__DIR__ . '/../../config/testconfig.json');

        if (!$filePath || !file_exists($filePath)) {
            error_log('TestConfig file not found, please create one at config/testconfig.json');
            return;
        }

        $config = json_decode(file_get_contents($filePath), true);

        $this->setPathConfig($config['Path'] ?? null);
        $this->setClientConfig($config['Client'] ?? []);
        $this->setDocumentConfig($config['Document'] ?? []);
        $this->tenantIds = $config['TenantIDs'] ?? null;
    }

    private function setPathConfig(array $pathConfig): void {
        foreach ($pathConfig as $value) {
            if ($value['enabled'] && $value['key'] === 'internalStore') {
                $this->internalStorePath = $value['value'];
            }
        }
    }

    private function setClientConfig(array $clientConfig): void {
        foreach ($clientConfig as $value) {
            if ($value['enabled'] && $value['key'] === 'id') {
                $this->id = $value['value'];
            }
        }
    }

    private function setDocumentConfig(array $documentConfig): void {
        foreach ($documentConfig as $value) {
            if ($value['enabled']) {
                match ($value['key']) {
                    'number' => $this->number = (int)$value['value'],
                    'correspondence_partner_guid' => $this->correspondence_partner_guid = $value['value'],
                    default => null,
                };
            }
        }
    }

    public function getInternalStorePath(): ?string {
        return $this->internalStorePath;
    }

    public function getDocumentNumber(): ?int {
        return $this->number;
    }

    public function getCorrespondencePartnerGuid(): ?string {
        return $this->correspondence_partner_guid;
    }

    public function getID(): ?string {
        return $this->id;
    }

    public function getClientID(): ClientID {
        return new ClientID($this->id);
    }

    public function getTenantIds(): ?array {
        return $this->tenantIds;
    }
}

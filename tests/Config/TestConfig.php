<?php

declare(strict_types=1);

namespace Tests\Config;

use App\Factories\LoggerFactory;
use ConfigToolkit\ConfigLoader;
use Datev\Entities\ClientMasterData\Clients\ClientID;
use Exception;

class TestConfig {
    private static ?TestConfig $instance = null;
    private ConfigLoader $configLoader;

    private function __construct() {
        $this->configLoader = ConfigLoader::getInstance(LoggerFactory::getLogger());
        $this->configLoader->loadConfigFile(__DIR__ . '/../../config/testconfig.json');
    }

    public static function getInstance(): TestConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConfigured(): bool {
        return $this->getInternalStorePath() !== null
            && $this->getDocumentNumber() !== null
            && $this->getCorrespondencePartnerGuid() !== null
            && $this->getID() !== null;
    }

    public function getInternalStorePath(): ?string {
        return $this->configLoader->get('Path', 'internalStore');
    }

    public function getDocumentNumber(): ?int {
        return (int) $this->configLoader->get('Document', 'number');
    }

    public function getCorrespondencePartnerGuid(): ?string {
        return $this->configLoader->get('Document', 'correspondence_partner_guid');
    }

    public function getID(): ?string {
        return $this->configLoader->get('Client', 'id');
    }

    public function getClientID(): ClientID {
        $id = $this->getID();
        if (!$id) {
            throw new Exception("Client ID ist nicht konfiguriert.");
        }
        return new ClientID($id);
    }

    public function getTenantIds(): ?array {
        return $this->configLoader->get('TenantIDs', 'values', []);
    }
}
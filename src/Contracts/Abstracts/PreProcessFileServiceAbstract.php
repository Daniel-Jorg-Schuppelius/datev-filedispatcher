<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use App\Config\Config;
use App\Contracts\Interfaces\PreProcessFileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use App\Traits\FileServiceTrait;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class PreProcessFileServiceAbstract implements PreProcessFileServiceInterface {
    use FileServiceTrait;

    public function __construct(string $filename, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        $this->clientsEndpoint = new ClientsEndpoint($client ?? APIClientFactory::getClient());
        $this->documentEndpoint = new DocumentsEndpoint($client ?? APIClientFactory::getClient());
        $this->logger = $logger ?? LoggerFactory::getLogger();
        $this->config = Config::getInstance();

        $this->filename = $filename;

        try {
            $this->extractDataFromFilename();
        } catch (\Exception $e) {
            $this->logger->error("Fehler bei der Verarbeitung des Dateinamens: " . $e->getMessage());
            throw $e;
        }
    }

    abstract protected function extractDataFromFilename(): void;

    abstract public function preProcess(): bool;
}

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

namespace App\Contracts\Abstracts\FileServices;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use App\Config\Config;
use App\Contracts\Interfaces\FileServices\FileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use App\Helper\FileSystem\File;
use App\Helper\InternalStoreMapper;
use App\Traits\FileServiceTrait;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use OutOfRangeException;
use Psr\Log\LoggerInterface;

abstract class FileServiceAbstract implements FileServiceInterface {
    use FileServiceTrait;

    protected const SUBFOLDER = '';

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

    public function getDestinationFolder(): ?string {
        $this->validateConfig();

        if (!is_null($this->client) && !is_null($this->document)) {
            return InternalStoreMapper::getInternalStorePath4Document($this->client, $this->document);
        } elseif (!is_null($this->client) && !empty($this->getSubFolder())) {
            return InternalStoreMapper::getInternalStorePath($this->client, $this->getSubFolder());
        }

        return null;
    }

    public function process(): void {
        $this->logger->info("Verarbeite Datei: {$this->filename} mit FileService: " . static::class . ".");
        File::move($this->filename, $this->getDestinationFolder());
    }

    protected function getSubFolder(): string {
        return static::SUBFOLDER;
    }

    protected function validateConfig(): void {
        if (is_null($this->config->getInternalStorePath())) {
            throw new OutOfRangeException("Ungültige Konfiguration für den internen Speicherpfad.");
        }
    }

    abstract protected function extractDataFromFilename(): void;
}

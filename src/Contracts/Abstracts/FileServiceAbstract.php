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
use App\Contracts\Interfaces\FileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use App\Helper\InternalStoreMapper;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;
use InvalidArgumentException;
use OutOfRangeException;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class FileServiceAbstract implements FileServiceInterface {
    protected const SUBFOLDER = '';
    protected const PATTERN = '';

    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;
    protected LoggerInterface $logger;

    protected Config $config;

    protected string $filename;
    protected ?Client $client = null;
    protected ?Document $document = null;

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

    public final function getFilename(): string {
        return $this->filename;
    }

    public final function getClient(): ?Client {
        return $this->client;
    }

    public final function getDocument(): ?Document {
        return $this->document;
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

    protected function getMatches(): array {
        $matches = [];
        if (!self::matchesPattern($this->filename, $matches)) {
            $this->logger->error("Ungültiger Dateiname: {$this->filename}");
            throw new InvalidArgumentException("Der Dateiname entspricht nicht dem erwarteten Muster: {$this->filename}");
        }

        return $matches;
    }

    protected function getSubFolder(): string {
        return static::SUBFOLDER;
    }

    protected function setClient(string $clientNumber): void {
        $this->client = $this->clientsEndpoint->search(["filter" => "number eq $clientNumber"])->getFirstValue();
        if (is_null($this->client)) {
            $this->logger->error("Client konnte nicht gefunden werden: $clientNumber");
            throw new RuntimeException("Client konnte nicht gefunden werden: $clientNumber");
        }
    }

    protected function setDocument(string $documentNumber): void {
        $this->document = $this->documentEndpoint->search(["filter" => "number eq $documentNumber"])->getFirstValue();
        if (is_null($this->document)) {
            $this->logger->error("Dokument konnte nicht gefunden werden: $documentNumber");
            throw new RuntimeException("Dokument konnte nicht gefunden werden: $documentNumber");
        }
    }

    protected function validateConfig(): void {
        if (is_null($this->config->getInternalStorePath())) {
            throw new OutOfRangeException("Ungültige Konfiguration für den internen Speicherpfad.");
        }
    }

    public static function getPattern(): string {
        if (empty(static::PATTERN)) {
            throw new RuntimeException("Kein Pattern in " . static::class . " definiert.");
        }

        return static::PATTERN;
    }

    public static function matchesPattern(string $filename, array &$matches = null): bool {
        return preg_match(static::getPattern(), basename($filename), $matches) === 1;
    }

    abstract protected function extractDataFromFilename(): void;
}

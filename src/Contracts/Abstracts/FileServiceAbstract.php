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
use App\Contracts\Interfaces\FileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Psr\Log\LoggerInterface;

abstract class FileServiceAbstract implements FileServiceInterface {
    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;
    protected LoggerInterface $logger;

    protected string $filename;
    protected ?Client $client = null;
    protected ?string $documentNumber = null;

    public function __construct(string $filename, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        $this->clientsEndpoint = new ClientsEndpoint($client ?? APIClientFactory::getClient());
        $this->documentEndpoint = new DocumentsEndpoint($client ?? APIClientFactory::getClient());
        $this->logger = $logger ?? LoggerFactory::getLogger();
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

    public final function getDocumentNumber(): ?string {
        return $this->documentNumber;
    }

    public static function matchesPattern(string $filename, array &$matches = null): bool {
        $result = preg_match(static::getPattern(), basename($filename), $matches);
        if ($result === false) {
            throw new \RuntimeException("Fehler beim Anwenden des Musters auf den Dateinamen: $filename");
        }
        return $result === 1;
    }

    abstract protected function extractDataFromFilename(): void;

    abstract public function getDestinationFolder(): ?string;

    abstract public static function getPattern(): string;
}

<?php

namespace App\Traits;

use App\Config\Config;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

trait FileServiceTrait {
    protected const PATTERN = '';

    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;
    protected LoggerInterface $logger;

    protected Config $config;

    protected string $filename;
    protected ?Client $client = null;
    protected ?Document $document = null;

    public final function getFilename(): string {
        return $this->filename;
    }

    public final function getClient(): ?Client {
        return $this->client;
    }

    public final function getDocument(): ?Document {
        return $this->document;
    }

    protected function getMatches(): array {
        $matches = [];
        if (!self::matchesPattern($this->filename, $matches)) {
            $this->logger->error("Ungültiger Dateiname: {$this->filename}");
            throw new InvalidArgumentException("Der Dateiname entspricht nicht dem erwarteten Muster: {$this->filename}");
        }

        return $matches;
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

    protected function setPropertiesFromDMS(string $documentNumber) {
        $this->setDocument($documentNumber);
        $this->client = $this->clientsEndpoint->get($this->document->getCorrespondencePartnerGUID());
        if (is_null($this->client)) {
            $this->logger->error("Client konnte nicht gefunden werden: $this->document->getCorrespondencePartnerGUID()");
            throw new RuntimeException("Client konnte nicht gefunden werden: $this->document->getCorrespondencePartnerGUID()");
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
}

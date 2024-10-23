<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Traits;

use App\Config\Config;
use App\Factories\LoggerFactory;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;
use Datev\Entities\Payroll\Clients\Client as PayrollClient;
use Datev\Entities\Payroll\Employees\Employees as PayrollEmployees;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

trait FileServiceTrait {
    protected const PATTERN = '';

    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;
    protected PayrollClientsEndpoint $payrollClientsEndpoint;
    protected LoggerInterface $logger;


    protected Config $config;

    protected string $file;
    protected ?Client $client = null;
    protected ?Document $document = null;
    protected ?PayrollClient $payrollClient = null;
    protected ?PayrollEmployees $payrollEmployees = null;

    public final function getFile(): string {
        return $this->file;
    }

    public final function getFilename(): string {
        return basename($this->file);
    }

    public final function getClient(): ?Client {
        return $this->client;
    }

    public final function getDocument(): ?Document {
        return $this->document;
    }

    protected function getMatches(): array {
        $matches = [];
        if (!self::matchesPattern($this->file, $matches)) {
            $this->logger->error("Ungültiger Dateiname: {$this->file}");
            throw new InvalidArgumentException("Der Dateiname entspricht nicht dem erwarteten Muster: {$this->file}");
        }
        $this->logger->debug("Matches für ServiceKlasse (" . static::class . "):" . implode(", ", $matches));

        return $matches;
    }

    protected function setClients(string $clientNumber): void {
        $this->setClient($clientNumber);
        $this->setPayrollClient($clientNumber);
    }

    protected function setClient(string $clientNumber): void {
        $clients = $this->clientsEndpoint->search(["filter" => "number eq $clientNumber"]);
        if (is_null($clients)) {
            $this->logger->error("Client konnte nicht gefunden werden: $clientNumber");
            $this->logger->notice("Client angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            throw new RuntimeException("Client konnte nicht gefunden werden: $clientNumber");
        }
        $this->client = $clients->getFirstValue();
    }

    protected function setPayrollClient(string $clientNumber): void {
        $payrollClients = $this->payrollClientsEndpoint->search();
        if (!is_null($payrollClients)) {
            $this->payrollClient = $payrollClients->getFirstValue("number", $clientNumber);
            if (is_null($this->payrollClient)) {
                $this->logger->error("Client (Payroll) konnte nicht gefunden werden: " . $clientNumber);
                $this->logger->notice("Client (Payroll) angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
                throw new RuntimeException("Client (Payroll) konnte nicht gefunden werden: " . $clientNumber);
            }
        } else {
            $this->logger->error("Es wurden keine Clients (Payroll) gefunden.");
            $this->logger->notice("Payroll aktiv? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            throw new RuntimeException("Es wurden keine Clients (Payroll) gefunden.");
        }
    }

    protected function setDocument(string $documentNumber): void {
        $documents = $this->documentEndpoint->search(["filter" => "number eq $documentNumber"]);
        if (is_null($documents)) {
            $this->logger->error("Dokument konnte im DMS nicht gefunden werden: $documentNumber");
            $this->logger->notice("Document im DMS gelöscht? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            throw new RuntimeException("Dokument konnte im DMS nicht gefunden werden: $documentNumber");
        }
        $this->document = $documents->getFirstValue();
    }

    protected function setPropertiesFromDMS(string $documentNumber, bool $withPayroll = false): void {
        $this->setDocument($documentNumber);

        $this->client = $this->clientsEndpoint->get($this->document->getCorrespondencePartnerGUID());
        if (is_null($this->client)) {
            $this->logger->error("Client (Client Master Data) konnte nicht gefunden werden: $this->document->getCorrespondencePartnerGUID()");
            throw new RuntimeException("Client (Client Master Data) konnte nicht gefunden werden: $this->document->getCorrespondencePartnerGUID()");
        }

        if ($withPayroll) {
            try {
                $this->setPayrollClient((string)$this->client->getNumber(), true);
            } catch (RuntimeException $e) {
                $this->logger->debug("Exception abgefangen: " . $e->getMessage());
            }
        }
    }

    public static function getPattern(): string {
        $logger = LoggerFactory::getLogger();

        if (empty(static::PATTERN)) {
            $logger->error("Kein Pattern in " . static::class . " definiert.");
            throw new RuntimeException("Kein Pattern in " . static::class . " definiert.");
        }

        return static::PATTERN;
    }

    public static function matchesPattern(string $file, array &$matches = null): bool {
        return preg_match(static::getPattern(), basename($file), $matches) === 1;
    }
}

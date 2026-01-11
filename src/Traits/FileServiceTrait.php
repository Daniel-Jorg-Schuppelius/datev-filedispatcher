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
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;
use Datev\Entities\Payroll\Clients\Client as PayrollClient;
use InvalidArgumentException;
use RuntimeException;

trait FileServiceTrait {
    protected const PATTERN = '';

    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;
    protected PayrollClientsEndpoint $payrollClientsEndpoint;

    protected Config $config;

    protected string $file;
    protected ?Client $client = null;
    protected ?Document $document = null;
    protected ?PayrollClient $payrollClient = null;

    public final function getFile(): string {
        return $this->file;
    }

    public final function getFilename(): string {
        return basename($this->file);
    }

    public final function getFolder(): string {
        return dirname($this->file);
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
            self::logErrorAndThrow(InvalidArgumentException::class, "Der Dateiname entspricht nicht dem erwarteten Muster: {$this->file}");
        }
        $this->logDebug("Matches für ServiceKlasse (" . static::class . "):" . implode(", ", $matches));

        return $matches;
    }

    protected function setClients(string $clientNumber): void {
        $this->setClient($clientNumber);
        $this->setPayrollClient($clientNumber);
    }

    protected function setClient(string $clientNumber): void {
        $clients = $this->clientsEndpoint->search(["filter" => "number eq $clientNumber"]);
        if (is_null($clients)) {
            $this->logNotice("Client angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            self::logErrorAndThrow(RuntimeException::class, "Client konnte nicht gefunden werden: $clientNumber");
        }
        $this->client = $clients->getFirstValue();
    }

    protected function setPayrollClient(string $clientNumber): void {
        $payrollClients = $this->payrollClientsEndpoint->search();
        if (!is_null($payrollClients)) {
            $payrollClient = $payrollClients->getFirstValue("number", $clientNumber);
            if (is_null($payrollClient)) {
                $this->logNotice("Client (Payroll) angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
                self::logErrorAndThrow(RuntimeException::class, "Client (Payroll) konnte nicht gefunden werden: $clientNumber");
            }

            $this->payrollClient = $this->payrollClientsEndpoint->get($payrollClient->getID()) ?? $payrollClient;
        } else {
            $this->logNotice("Payroll aktiv? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            self::logErrorAndThrow(RuntimeException::class, "Es wurden keine Clients (Payroll) gefunden.");
        }
    }

    protected function setDocument(string $documentNumber): void {
        $documents = $this->documentEndpoint->search(["filter" => "number eq $documentNumber"]);
        if (is_null($documents)) {
            $this->logNotice("Dokument im DMS gelöscht? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            self::logErrorAndThrow(RuntimeException::class, "Dokument konnte im DMS nicht gefunden werden: $documentNumber");
        }
        $this->document = $documents->getFirstValue();
    }

    protected function setPropertiesFromDMS(string $documentNumber, bool $withPayroll = false): void {
        $this->setDocument($documentNumber);

        $this->client = $this->clientsEndpoint->get($this->document->getCorrespondencePartnerGUID());
        if (is_null($this->client)) {
            self::logErrorAndThrow(RuntimeException::class, "Client (Client Master Data) konnte nicht gefunden werden: " . $this->document->getCorrespondencePartnerGUID());
        }

        if ($withPayroll) {
            try {
                $this->setPayrollClient((string)$this->client->getNumber(), true);
            } catch (RuntimeException $e) {
                $this->logDebug("Exception abgefangen: " . $e->getMessage());
            }
        }
    }

    public static function getPattern(): string {
        if (empty(static::PATTERN)) {
            self::logErrorAndThrow(RuntimeException::class, "Kein Pattern in " . static::class . " definiert.");
        }

        return static::PATTERN;
    }

    public static function matchesPattern(string $file, ?array &$matches = null): bool {
        return preg_match(static::getPattern(), basename($file), $matches) === 1;
    }
}

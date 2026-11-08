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

use APIToolkit\Exceptions\ApiException;
use App\Config\Config;
use DateTime;
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

    final public function getFile(): string {
        return $this->file;
    }

    final public function getFilename(): string {
        return basename($this->file);
    }

    final public function getFolder(): string {
        return dirname($this->file);
    }

    final public function getClient(): ?Client {
        return $this->client;
    }

    final public function getDocument(): ?Document {
        return $this->document;
    }

    /**
     * @return array<array-key, string>
     */
    protected function getMatches(): array {
        $matches = [];
        if (!self::matchesPattern($this->file, $matches)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Der Dateiname entspricht nicht dem erwarteten Muster: {$this->file}");
        }
        $this->logDebug("Matches für ServiceKlasse (" . static::class . "):" . implode(", ", $matches));

        return $matches;
    }

    /**
     * Setzt Client (Mandantendaten) und Client (Payroll). Ist der Mandant in den
     * Mandantendaten nicht auffindbar, wird er über den Client (Payroll) ermittelt.
     *
     * @param DateTime|null $payrollReferenceDate Stichtag für die Payroll-Abfragen, null = heute
     */
    protected function setClients(string $clientNumber, ?DateTime $payrollReferenceDate = null): void {
        $clientException = null;
        try {
            $this->setClient($clientNumber);
        } catch (RuntimeException $e) {
            $clientException = $e;
        }

        $this->setPayrollClient($clientNumber, $payrollReferenceDate);

        $payrollClient = $this->payrollClient;
        if (!is_null($this->client) || is_null($payrollClient)) {
            return;
        }

        $client = $this->clientsEndpoint->get($payrollClient->getID());
        if (is_null($client)) {
            self::logErrorAndThrow(RuntimeException::class, "Client konnte nicht aus den Payrolldaten ermittelt werden: $clientNumber", previous: $clientException);
        }

        $this->client = $client;
        $this->logNotice("Client wurde aus den Payrolldaten ermittelt: " . $payrollClient->getNumber() . " -> " . $client->getNumber());
    }

    protected function setClient(string $clientNumber): void {
        $clients = $this->clientsEndpoint->search(["filter" => "number eq $clientNumber"]);
        if (is_null($clients)) {
            $this->logNotice("Client angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            self::logErrorAndThrow(RuntimeException::class, "Client konnte nicht gefunden werden: $clientNumber");
        }
        $this->client = $clients->getFirstValue();
    }

    /**
     * Lädt den Client (Payroll) zum Stichtag. Liegt der Stichtag in der Vergangenheit
     * und ist der Client dort nicht ermittelbar (z. B. archiviertes Abrechnungsjahr),
     * wird auf den aktuellen Stand zurückgegriffen.
     *
     * @param DateTime|null $referenceDate Stichtag, null = heute
     */
    protected function setPayrollClient(string $clientNumber, ?DateTime $referenceDate = null): void {
        $today = new DateTime('today');

        if (!is_null($referenceDate) && $referenceDate->format('Y-m-d') !== $today->format('Y-m-d')) {
            try {
                $this->payrollClient = $this->fetchPayrollClient($clientNumber, $referenceDate);
                return;
            } catch (ApiException|RuntimeException $e) {
                $this->logNotice("Client (Payroll) $clientNumber zum Stichtag {$referenceDate->format('Y-m-d')} nicht ermittelbar ({$e->getMessage()}), versuche den aktuellen Stand.");
            }
        }

        try {
            $this->payrollClient = $this->fetchPayrollClient($clientNumber, $today);
        } catch (ApiException $e) {
            $this->logNotice("Payroll-Schnittstelle meldet HTTP {$e->getCode()}. Server in Sicherung oder Wartung? Ursache im DATEVconnect-Protokoll prüfen, die Datei wird beim nächsten Lauf erneut verarbeitet.");
            throw $e;
        } catch (RuntimeException $e) {
            $this->logNotice("Payroll aktiv und Client (Payroll) angelegt? Server nicht erreichbar oder in Sicherung? Bitte prüfen und ggf. aus dem Ordner löschen.");
            self::logErrorAndThrow(RuntimeException::class, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws ApiException bei einem Fehler der Payroll-Schnittstelle
     * @throws RuntimeException wenn keine Clients (Payroll) oder nicht der gesuchte geliefert werden
     */
    private function fetchPayrollClient(string $clientNumber, DateTime $referenceDate): PayrollClient {
        $payrollClients = $this->payrollClientsEndpoint->search(['reference-date' => $referenceDate->format('Y-m-d')]);
        if (is_null($payrollClients)) {
            throw new RuntimeException("Es wurden keine Clients (Payroll) gefunden.");
        }

        $payrollClient = $payrollClients->getFirstValue("number", $clientNumber);
        if (is_null($payrollClient)) {
            throw new RuntimeException("Client (Payroll) konnte nicht gefunden werden: $clientNumber");
        }

        return $this->payrollClientsEndpoint->get($payrollClient->getID(), referenceDate: $referenceDate) ?? $payrollClient;
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

        $document = $this->document;
        if (is_null($document)) {
            self::logErrorAndThrow(RuntimeException::class, "Dokument konnte im DMS nicht gefunden werden: $documentNumber");
        }

        $correspondencePartnerGUID = $document->getCorrespondencePartnerGUID();

        $client = $this->clientsEndpoint->get($correspondencePartnerGUID);
        if (is_null($client)) {
            self::logErrorAndThrow(RuntimeException::class, "Client (Client Master Data) konnte nicht gefunden werden: " . $correspondencePartnerGUID);
        }
        $this->client = $client;

        if ($withPayroll) {
            try {
                $this->setPayrollClient((string) $client->getNumber());
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

    /**
     * Die Patterns nutzen benannte Gruppen, daher enthält $matches sowohl
     * numerische als auch benannte Schlüssel.
     *
     * @param array<array-key, string> $matches
     * @param-out array<array-key, string> $matches
     */
    public static function matchesPattern(string $file, array &$matches = []): bool {
        return preg_match(static::getPattern(), basename($file), $matches) === 1;
    }
}

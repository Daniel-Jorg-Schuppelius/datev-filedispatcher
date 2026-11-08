<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileHandlerAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use APIToolkit\Exceptions\ApiException;
use App\Config\Config;
use App\Contracts\Interfaces\FileServices\FileHandlerInterface;
use App\Factories\APIClientFactory;
use DateTime;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;
use Datev\Entities\Payroll\Clients\Client as PayrollClient;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Gemeinsame Basis von FileServices und PreProcessServices: Pattern-Matching,
 * Dateiangaben und die Ermittlung von Dokument, Client und Client (Payroll).
 *
 * Die DATEV-API wird erst beim ersten Zugriff angesprochen. Services, die nur
 * Dateinamen umbauen, brauchen dadurch weder API-Zugang noch Zugangsdaten.
 */
abstract class FileHandlerAbstract implements FileHandlerInterface {
    use ErrorLog;

    protected const PATTERN = '';

    /** Siehe {@see FileHandlerInterface::getPriority()}. */
    protected const PRIORITY = 0;

    protected Config $config;

    protected string $file;
    protected ?Client $client = null;
    protected ?Document $document = null;
    protected ?PayrollClient $payrollClient = null;

    private ?ApiClientInterface $apiClient;
    private ?ClientsEndpoint $clientsEndpoint = null;
    private ?DocumentsEndpoint $documentsEndpoint = null;
    private ?PayrollClientsEndpoint $payrollClientsEndpoint = null;

    public function __construct(string $file, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        self::setLogger($logger);
        $this->config = Config::getInstance();
        $this->apiClient = $client;
        $this->file = $file;

        // Exceptions werden vom FileDispatcher geloggt, hier nicht erneut.
        $this->extractDataFromFile();
    }

    abstract protected function extractDataFromFile(): void;

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

    public static function getPattern(): string {
        if (empty(static::PATTERN)) {
            self::logErrorAndThrow(RuntimeException::class, "Kein Pattern in " . static::class . " definiert.");
        }

        return static::PATTERN;
    }

    public static function getPriority(): int {
        return static::PRIORITY;
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

    // API-Zugriff (lazy)

    protected function getApiClient(): ApiClientInterface {
        return $this->apiClient ??= APIClientFactory::getClient();
    }

    protected function getClientsEndpoint(): ClientsEndpoint {
        return $this->clientsEndpoint ??= new ClientsEndpoint($this->getApiClient(), self::$logger);
    }

    protected function getDocumentsEndpoint(): DocumentsEndpoint {
        return $this->documentsEndpoint ??= new DocumentsEndpoint($this->getApiClient(), self::$logger);
    }

    protected function getPayrollClientsEndpoint(): PayrollClientsEndpoint {
        return $this->payrollClientsEndpoint ??= new PayrollClientsEndpoint($this->getApiClient(), self::$logger);
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

        $client = $this->getClientsEndpoint()->get($payrollClient->getID());
        if (is_null($client)) {
            self::logErrorAndThrow(RuntimeException::class, "Client konnte nicht aus den Payrolldaten ermittelt werden: $clientNumber", previous: $clientException);
        }

        $this->client = $client;
        $this->logNotice("Client wurde aus den Payrolldaten ermittelt: " . $payrollClient->getNumber() . " -> " . $client->getNumber());
    }

    protected function setClient(string $clientNumber): void {
        self::assertNumeric($clientNumber, 'Mandantennummer');

        $clients = $this->getClientsEndpoint()->search(["filter" => "number eq $clientNumber"]);
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
        $endpoint = $this->getPayrollClientsEndpoint();

        $payrollClients = $endpoint->search(['reference-date' => $referenceDate->format('Y-m-d')]);
        if (is_null($payrollClients)) {
            throw new RuntimeException("Es wurden keine Clients (Payroll) gefunden.");
        }

        $payrollClient = $payrollClients->getFirstValue("number", $clientNumber);
        if (is_null($payrollClient)) {
            throw new RuntimeException("Client (Payroll) konnte nicht gefunden werden: $clientNumber");
        }

        return $endpoint->get($payrollClient->getID(), referenceDate: $referenceDate) ?? $payrollClient;
    }

    protected function setDocument(string $documentNumber): void {
        self::assertNumeric($documentNumber, 'Dokumentnummer');

        $documents = $this->getDocumentsEndpoint()->search(["filter" => "number eq $documentNumber"]);
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

        $client = $this->getClientsEndpoint()->get($correspondencePartnerGUID);
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

    /**
     * Die Nummern landen in OData-Filtern. Die Patterns lassen ohnehin nur Ziffern zu,
     * die Prüfung schützt zusätzlich vor künftigen Patterns mit weiteren Zeichen.
     */
    private static function assertNumeric(string $value, string $label): void {
        if (!ctype_digit($value)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige $label: '$value'");
        }
    }
}

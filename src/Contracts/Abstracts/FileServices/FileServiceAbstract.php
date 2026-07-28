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
use CommonToolkit\Helper\FileSystem\File;
use App\Helper\InternalStoreMapper;
use App\Traits\FileServiceTrait;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use ERRORToolkit\Traits\ErrorLog;
use Exception;
use OutOfRangeException;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class FileServiceAbstract implements FileServiceInterface {
    use ErrorLog, FileServiceTrait;

    protected const SUBFOLDER = '';

    public function __construct(string $file, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        self::setLogger($logger);
        $this->config = Config::getInstance();

        $client = $client ?? APIClientFactory::getClient();
        $this->clientsEndpoint = new ClientsEndpoint($client, self::$logger);
        $this->documentEndpoint = new DocumentsEndpoint($client, self::$logger);
        $this->payrollClientsEndpoint = new PayrollClientsEndpoint($client, self::$logger);

        $this->file = $file;

        try {
            $this->extractDataFromFile();
        } catch (Exception $e) {
            self::logException($e);
            throw $e;
        }
    }

    public function getDestinationFolder(): ?string {
        $this->validateConfig();

        $client = $this->client;
        $document = $this->document;

        if (!is_null($client) && !is_null($document)) {
            return InternalStoreMapper::getInternalStorePath4Document($client, $document);
        } elseif (!is_null($client) && !empty($this->getSubFolder())) {
            return InternalStoreMapper::getInternalStorePath($client, $this->getSubFolder());
        }

        return null;
    }

    public function process(): void {
        $this->logNotice("Verarbeite Datei: {$this->file} mit FileService: " . static::class . ".");

        $destinationFolder = $this->getDestinationFolder();
        if (is_null($destinationFolder)) {
            self::logErrorAndThrow(RuntimeException::class, "Zielordner konnte nicht bestimmt werden für die Datei: {$this->file}");
        }

        File::move($this->file, $destinationFolder, $this->getDestinationFilename());
    }

    protected function getDestinationFilename(): string {
        return $this->getFilename();
    }

    protected function getSubFolder(): string {
        return static::SUBFOLDER;
    }

    protected function validateConfig(): void {
        if (is_null($this->config->getInternalStorePath())) {
            self::logCriticalAndThrow(OutOfRangeException::class, "Ungültige Konfiguration für den internen Speicherpfad.");
        }
    }

    abstract protected function extractDataFromFile(): void;
}

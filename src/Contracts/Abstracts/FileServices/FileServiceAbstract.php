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
use CommonToolkit\Helper\FileSystem\File;
use App\Helper\InternalStoreMapper;
use App\Traits\FileServiceTrait;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use Exception;
use OutOfRangeException;
use Psr\Log\LoggerInterface;

abstract class FileServiceAbstract implements FileServiceInterface {
    use FileServiceTrait;

    protected const SUBFOLDER = '';

    public function __construct(string $file, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        self::$logger = $logger ?? LoggerFactory::getLogger();
        $this->config = Config::getInstance();

        $client = $client ?? APIClientFactory::getClient();
        $this->clientsEndpoint = new ClientsEndpoint($client, self::$logger);
        $this->documentEndpoint = new DocumentsEndpoint($client, self::$logger);
        $this->payrollClientsEndpoint = new PayrollClientsEndpoint($client, self::$logger);

        $this->file = $file;

        try {
            $this->extractDataFromFile();
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Verarbeitung der Datei: $file (" . $e->getMessage() . ")");
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
        self::$logger->notice("Verarbeite Datei: {$this->file} mit FileService: " . static::class . ".");
        File::move($this->file, $this->getDestinationFolder(), $this->getDestinationFilename());
    }

    protected function getDestinationFilename(): string {
        return $this->getFilename();
    }

    protected function getSubFolder(): string {
        return static::SUBFOLDER;
    }

    protected function validateConfig(): void {
        if (is_null($this->config->getInternalStorePath())) {
            self::$logger->critical("Ungültige Konfiguration für den internen Speicherpfad.");
            throw new OutOfRangeException("Ungültige Konfiguration für den internen Speicherpfad.");
        }
    }

    abstract protected function extractDataFromFile(): void;
}

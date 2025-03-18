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
use App\Contracts\Interfaces\FileServices\PreProcessFileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use App\Traits\FileServiceTrait;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use ERRORToolkit\Traits\ErrorLog;
use Exception;
use Psr\Log\LoggerInterface;

abstract class PreProcessFileServiceAbstract implements PreProcessFileServiceInterface {
    use ErrorLog, FileServiceTrait;

    public function __construct(string $file, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        self::setLogger($logger ?? LoggerFactory::getLogger());
        $this->config = Config::getInstance();

        $client = $client ?? APIClientFactory::getClient();
        $this->clientsEndpoint = new ClientsEndpoint($client, self::$logger);
        $this->documentEndpoint = new DocumentsEndpoint($client, self::$logger);
        $this->payrollClientsEndpoint = new PayrollClientsEndpoint($client, self::$logger);

        $this->file = $file;

        try {
            $this->extractDataFromFile();
        } catch (Exception $e) {
            $this->logError("Fehler bei der Verarbeitung des Dateinamens: " . $e->getMessage());
            throw $e;
        }
    }

    abstract protected function extractDataFromFile(): void;

    abstract public function preProcess(): bool;
}

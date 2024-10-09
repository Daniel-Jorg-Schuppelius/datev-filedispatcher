<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Contracts\Abstracts;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use APIToolkit\Logger\ConsoleLoggerFactory;
use App\Contracts\Interfaces\FileServiceInterface;
use App\Factories\APIClientFactory;
use App\Factories\StorageFactory;
use Datev\API\Desktop\Endpoints\Accounting\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Psr\Log\LoggerInterface;

abstract class FileServiceAbstract implements FileServiceInterface {
    protected ClientsEndpoint $clientsEndpoint;
    protected DocumentsEndpoint $documentEndpoint;

    protected LoggerInterface $logger;

    protected string $destinationFolder;
    protected string $filename;

    protected string $documentNumber;
    protected string $tenant;

    public function __construct(string $filename, ?string $destinationFolder = null, ApiClientInterface $client = null, LoggerInterface $logger = null) {
        $this->clientsEndpoint = new ClientsEndpoint($client ?? APIClientFactory::getClient());
        $this->documentEndpoint = new DocumentsEndpoint($client ?? APIClientFactory::getClient());
        $this->logger = $logger ?? ConsoleLoggerFactory::getLogger();
        $this->destinationFolder = $destinationFolder ?? StorageFactory::getInternalStorePath();
        $this->filename = $filename;
        $this->extractDatafromFilename();
    }

    public static function matchesPattern(string $filename, array &$matches = null): bool {
        return preg_match(static::getPattern(), basename($filename), $matches) === 1;
    }

    public function getDocumentNumber(): string {
        return $this->documentNumber;
    }

    abstract protected function extractDatafromFilename(): void;
    abstract public static function getPattern(): string;
}

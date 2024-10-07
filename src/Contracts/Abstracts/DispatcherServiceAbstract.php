<?php

namespace App\Contracts\Abstracts;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use APIToolkit\Logger\ConsoleLoggerFactory;
use App\Contracts\Interfaces\DispatcherInterface;
use App\Factories\APIClientFactory;
use App\Factories\StorageFactory;
use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Psr\Log\LoggerInterface;

abstract class DispatcherServiceAbstract implements DispatcherInterface {
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

    /**
     * Extrahiert Klassendaten basierend auf dem Dateinamen.
     *
     * @return void
     */

    abstract protected function extractDatafromFilename(): void;
}

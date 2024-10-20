<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Endpoints\DocumentManagement;

use Datev\API\Desktop\Endpoints\ClientMasterData\ClientsEndpoint;
use Datev\API\Desktop\Endpoints\DocumentManagement\DocumentsEndpoint;
use Datev\Entities\DocumentManagement\Documents\Documents;
use Tests\Config\TestConfig;
use Tests\Contracts\EndpointTest;
use Tests\TestDispatcherFactory;

class DocumentTest extends EndpointTest {
    protected ?ClientsEndpoint $preEndpoint;
    protected ?DocumentsEndpoint $endpoint;

    protected TestConfig $testConfig;

    public function __construct($name) {
        parent::__construct($name);
        $this->preEndpoint = new ClientsEndpoint($this->client, $this->logger);
        $this->endpoint = new DocumentsEndpoint($this->client, $this->logger);

        $this->testConfig = TestConfig::getInstance();
        $this->apiDisabled = true; // API is disabled
    }

    public function testGetClient() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }
        $documents = $this->endpoint->search(["filter" => "number eq " . $this->testConfig->getDocumentNumber()]);
        $this->assertInstanceOf(Documents::class, $documents);
        $document = $documents->getValues()[0];
        $client = $this->preEndpoint->get($document->getCorrespondencePartnerGUID());
    }
}

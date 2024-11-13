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
use Datev\API\Desktop\Endpoints\Payroll\ClientsEndpoint as PayrollClientsEndpoint;
use Datev\Entities\DocumentManagement\Documents\Documents;
use Datev\Entities\Payroll\Clients\Client as PayrollClient;
use Tests\Config\TestConfig;
use Tests\Contracts\EndpointTest;

class DocumentTest extends EndpointTest {
    protected ?ClientsEndpoint $preEndpoint;
    protected ?PayrollClientsEndpoint $payrollEndpoint;
    protected ?DocumentsEndpoint $endpoint;

    protected TestConfig $testConfig;

    public function __construct($name) {
        parent::__construct($name);
        $this->preEndpoint = new ClientsEndpoint($this->client, $this->logger);
        $this->endpoint = new DocumentsEndpoint($this->client, $this->logger);

        $this->payrollEndpoint = new PayrollClientsEndpoint($this->client, $this->logger);

        $this->testConfig = TestConfig::getInstance();
        $this->apiDisabled = false; // API is disabled
    }

    public function testGetClient() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }
        $documents = $this->endpoint->search(["filter" => "number eq " . $this->testConfig->getDocumentNumber()]);
        $this->assertInstanceOf(Documents::class, $documents);
        $document = $documents->getFirstValue();
        $client = $this->preEndpoint->get($document->getCorrespondencePartnerGUID());
    }

    public function testGetClientFromPayroll() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $client = $this->payrollEndpoint->search()->getFirstValue("number", "40699");
        $client = $this->payrollEndpoint->get($client->getID());
        $otherClient = $this->preEndpoint->get($client->getID());
        $this->assertInstanceOf(PayrollClient::class, $client);
    }
}

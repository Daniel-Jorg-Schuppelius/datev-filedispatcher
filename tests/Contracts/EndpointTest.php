<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EndpointTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Contracts;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use App\Config\Config;
use App\Factories\APIClientFactory;
use App\Factories\LoggerFactory;
use Datev\API\Desktop\Endpoints\Diagnostics\EchoEndpoint;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class EndpointTest extends TestCase {
    protected ?LoggerInterface $logger = null;

    protected ?ApiClientInterface $client;

    protected bool $apiDisabled = false;

    public function __construct($name) {
        parent::__construct($name);
        $config = Config::getInstance();
        $config->setDebug(true);
        $this->logger = LoggerFactory::getLogger();
        $this->client = APIClientFactory::getClient();
    }

    final protected function setUp(): void {
        if (!$this->apiDisabled) {
            try {
                $endpoint = new EchoEndpoint($this->client);
                $echoResponse = $endpoint->get();
                $this->apiDisabled = !$echoResponse->isValid();
            } catch (\Exception $e) {
                error_log("API disabled -> " . $e->getMessage());
                $this->apiDisabled = true;
            }
        }
    }
}

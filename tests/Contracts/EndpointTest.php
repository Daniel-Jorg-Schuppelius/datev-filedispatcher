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
use CommonToolkit\Helper\FileSystem\File;
use Datev\API\Desktop\Endpoints\Diagnostics\EchoEndpoint;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Config\TestConfig;

abstract class EndpointTest extends TestCase {
    protected ?LoggerInterface $logger = null;

    protected ?ApiClientInterface $client;

    protected TestConfig $testConfig;

    protected array $tenantIds;
    protected string $testFile;

    protected bool $apiDisabled = false;

    public function __construct($name) {
        parent::__construct($name);
        $config = Config::getInstance();
        $config->setDebug(true);
        $this->logger = LoggerFactory::getLogger();
        $this->client = APIClientFactory::getClient();

        $this->testConfig = TestConfig::getInstance();
        $this->tenantIds = $this->testConfig->getTenantIds();
    }

    final protected function setUp(): void {

        foreach ($this->tenantIds as $tenantId) {
            $tempDir = str_replace("{tenant}", (string)$tenantId, $this->testConfig->getInternalStorePath());

            if (!is_dir(dirname($tempDir))) {
                mkdir(dirname($tempDir));
            }
            if (!is_dir($tempDir)) {
                mkdir($tempDir);
            }

            $categories = [
                "04 Sonstiges/Belege",
                "04 Sonstiges/Einkommensbescheinigungen",
                "02 Entgeltabrechnung/Vorjahre/2022/01 Januar",
                "02 Entgeltabrechnung/2023/09 September",
                "02 Entgeltabrechnung/2024/10 Oktober",
                "01 Finanzbuchhaltung/2023/FA Mahnungen, Umbuchung etc"
            ];

            foreach ($categories as $category) {
                $fullPath = $tempDir . '/' . $category;
                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0777, true);
                }
            }
        }

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

    protected function tearDown(): void {
        foreach ($this->tenantIds as $tenantId) {
            $tempDir = str_replace("{tenant}", (string)$tenantId, $this->testConfig->getInternalStorePath());
            if (is_dir($tempDir)) {
                foreach (
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    ) as $fileInfo
                ) {
                    // Überprüfen, ob es sich um eine Datei oder ein Verzeichnis handelt und dann entsprechend löschen
                    if ($fileInfo->isDir()) {
                        rmdir($fileInfo->getRealPath());  // Verzeichnis entfernen
                    } else {
                        unlink($fileInfo->getRealPath());  // Datei entfernen
                    }
                }
                rmdir($tempDir);  // Hauptverzeichnis entfernen
            }
        }
        if (!empty($this->testFile) && !File::exists($this->testFile)) {
            copy($this->testFile . '.bak', $this->testFile);
        }
        parent::tearDown();
    }
}

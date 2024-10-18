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
use App\Factories\StorageFactory;
use App\Helper\FileSystem\File;
use Datev\API\Desktop\Endpoints\Diagnostics\EchoEndpoint;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class EndpointTest extends TestCase {
    protected ?LoggerInterface $logger = null;

    protected ?ApiClientInterface $client;

    protected string $internalStorePath;
    protected string $tempDir;
    protected string $testFile;

    protected bool $apiDisabled = false;

    public function __construct($name) {
        parent::__construct($name);
        $config = Config::getInstance();
        $config->setDebug(true);
        $this->logger = LoggerFactory::getLogger();
        $this->client = APIClientFactory::getClient();
    }

    final protected function setUp(): void {
        $this->internalStorePath = sys_get_temp_dir() . '/internal_store_test/{tenant}';
        $this->tempDir = str_replace("{tenant}", "20542", $this->internalStorePath);

        if (!is_dir(dirname($this->tempDir))) {
            mkdir(dirname($this->tempDir));
        }
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }

        StorageFactory::setInternalStorePath($this->internalStorePath);

        $categories = [
            "04 Sonstiges/Einkommensbescheinigungen",
            "02 Entgeltabrechnung/2023/09 September",
            "01 Finanzbuchhaltung/2023/FA Mahnungen, Umbuchung etc"
        ];

        foreach ($categories as $category) {
            $fullPath = $this->tempDir . '/' . $category;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0777, true);
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
        if (is_dir($this->tempDir)) {
            foreach (
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
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
            rmdir($this->tempDir);  // Hauptverzeichnis entfernen
        }
        if (!File::exists($this->testFile)) {
            copy($this->testFile . '.bak', $this->testFile);
        }
        parent::tearDown();
    }
}

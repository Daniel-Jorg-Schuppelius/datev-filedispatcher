<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DMSBasicFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services;

use App\Helper\FileDispatcher;
use App\Services\DMSBasicFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class DMSBasicFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/219628 - Lohn Mandantenunterlagen.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        $matches = [];
        $this->assertTrue(DMSBasicFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(3, $matches);
    }

    public function testMatchesPattern(): void {
        $this->assertTrue(DMSBasicFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(DMSBasicFileService::matchesPattern($invalidFilename));
    }

    public function testDMSBasicFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $service = new DMSBasicFileService($this->testFile);
        $service->process();

        $this->assertEquals('219628', $service->getDocument()->getNumber());
    }

    public function testFileDispatcherDMSBasicFileServiceProcessing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);
    }
}

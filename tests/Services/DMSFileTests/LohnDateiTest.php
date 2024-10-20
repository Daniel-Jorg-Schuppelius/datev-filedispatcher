<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DMSBasicFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services\DMSFileTests;

use App\Services\DMSBasicFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;
use Tests\Services\DMSBasicFileServiceTest;

class LohnDateiTest extends DMSBasicFileServiceTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../../.samples/219624 - Lohn - 2022_1.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }
        $matches = [];
        $this->assertTrue(DMSBasicFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(4, $matches);
    }

    public function testDMSBasicFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new DMSBasicFileService($this->testFile);
        $service->process();

        $this->assertEquals('219624', $service->getDocument()->getNumber());
    }
}

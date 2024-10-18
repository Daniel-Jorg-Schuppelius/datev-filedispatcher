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
use Tests\Services\DMSBasicFileServiceTest;

class BelegeTest extends DMSBasicFileServiceTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../../.samples/219625 - Belege.pdf');
        $this->apiDisabled = false; // API is disabled
    }

    public function testPatternMatching() {
        $matches = [];
        $this->assertTrue(DMSBasicFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(3, $matches);
    }

    public function testDMSBasicFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $service = new DMSBasicFileService($this->testFile);
        $service->process();

        $this->assertEquals('219625', $service->getDocument()->getNumber());
    }
}

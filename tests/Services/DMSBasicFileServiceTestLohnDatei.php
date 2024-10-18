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

use App\Services\DMSBasicFileService;

class DMSBasicFileServiceTestLohnDatei extends DMSBasicFileServiceTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/219624 - Lohn - 2022_1.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        $matches = [];
        $this->assertTrue(DMSBasicFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(4, $matches);
    }

    public function testDMSBasicFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $service = new DMSBasicFileService($this->testFile);
        $service->process();

        $this->assertEquals('219624', $service->getDocument()->getNumber());
    }
}

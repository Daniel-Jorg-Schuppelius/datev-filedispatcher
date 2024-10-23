<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\PreProcessServices;

use App\Helper\FileDispatcher;
use App\PreProcessServices\ATimeCodeProcessFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class TimeCodeProcessFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        // $this->testFile = realpath(__DIR__ . '/../../.samples/20542_10_2024_Brutto_Netto - 20241021132856_00001_AA0.pdf');
        $this->testFile = realpath(__DIR__ . '/../../.samples/20542_10_2024_Brutto_Netto - 20241021_132856_00001_AA0.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(ATimeCodeProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(2, $matches);
    }

    public function testMatchesPattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(ATimeCodeProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(ATimeCodeProcessFileService::matchesPattern($invalidFilename));
    }

    public function testPayrollFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new ATimeCodeProcessFileService($this->testFile);
        $service->preProcess();

        $this->assertEquals('20542', $service->getClient()->getNumber());
    }

    public function testFileDispatcherPayrollFileServiceProcessing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);
    }
}

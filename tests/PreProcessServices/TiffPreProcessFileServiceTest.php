<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TiffPreProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services;

use App\Helper\FileDispatcher;
use App\PreProcessServices\TiffPreProcessFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class TiffPreProcessFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_1.tif');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        $matches = [];
        $this->assertTrue(TiffPreProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(3, $matches);
    }

    public function testMatchesPattern(): void {
        $this->assertTrue(TiffPreProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(TiffPreProcessFileService::matchesPattern($invalidFilename));
    }

    public function testTiffPreProcessFileServicePreProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $service = new TiffPreProcessFileService($this->testFile);
        $service->preProcess();
        $this->assertFileExists(realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081.pdf'));
    }

    public function testFileDispatcherTiffPreProcessFileServiceProcessing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);
    }
}

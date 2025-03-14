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
use CommonToolkit\Helper\FileSystem\File;
use App\PreProcessServices\PDFFileNameProcessFileService;
use App\PreProcessServices\PDFScannerCodeProcessFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class PDFScannerCodeProcessFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/235309 - BvFA ESt-Bescheid 2023 - S25C-924070208151.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(PDFScannerCodeProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(2, $matches);
    }

    public function testMatchesPattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(PDFScannerCodeProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(PDFScannerCodeProcessFileService::matchesPattern($invalidFilename));
    }

    public function testPayrollFileServiceProcessing() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new PDFScannerCodeProcessFileService($this->testFile);
        $service->preProcess();

        $this->assertInstanceOf(PDFScannerCodeProcessFileService::class, $service);
        File::delete(__DIR__ . '/../../.samples/235309 - BvFA ESt-Bescheid 2023.pdf');
    }
}

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

use CommonToolkit\Helper\FileSystem\File;
use App\PreProcessServices\PDFNameProcessFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class PDFNameProcessFileServiceTest extends DocumentTest {
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
        $this->assertTrue(PDFNameProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(2, $matches);
    }

    public function testMatchesPattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(PDFNameProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(PDFNameProcessFileService::matchesPattern($invalidFilename));
    }

    public function testPayrollFileServiceProcessing() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new PDFNameProcessFileService($this->testFile);
        $service->preProcess();

        $this->assertInstanceOf(PDFNameProcessFileService::class, $service);
        File::delete(__DIR__ . '/../../.samples/235309 - ESt-Bescheid 2023 - S25C-924070208151.pdf');
    }
}

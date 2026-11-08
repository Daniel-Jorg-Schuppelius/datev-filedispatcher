<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PDFTimeCodeProcessFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\PreProcessServices;

use App\PreProcessServices\PDFTimeCodeProcessFileService;
use CommonToolkit\Helper\FileSystem\File;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class PDFTimeCodeProcessFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        // $this->testFile = realpath(__DIR__ . '/../../.samples/20542_11_2024_Brutto_Netto - 20241021132856_00001_AA0.pdf');
        $this->testFile = realpath(__DIR__ . '/../../.samples/20542_11_2024_Brutto_Netto - 20241021_132856_00001_AA0.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function test_pattern_matching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(PDFTimeCodeProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(2, $matches);
    }

    public function test_matches_pattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(PDFTimeCodeProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(PDFTimeCodeProcessFileService::matchesPattern($invalidFilename));
    }

    public function test_payroll_file_service_processing() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new PDFTimeCodeProcessFileService($this->testFile);
        $service->preProcess();

        $this->assertInstanceOf(PDFTimeCodeProcessFileService::class, $service);
        File::delete(__DIR__ . '/../../.samples/20542_11_2024_Brutto_Netto_00001_AA0.pdf');
    }
}

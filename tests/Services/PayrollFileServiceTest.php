<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services;

use App\Helper\FileDispatcher;
use App\Services\Payroll\PayrollFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class PayrollFileServiceTest extends DocumentTest {
    protected string $testFile;

    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/20542_00001_Wegner_Regina_09_2023_Brutto_Netto_O04.pdf');
        $this->apiDisabled = false; // API is disabled
    }

    public function testPatternMatching() {
        $matches = [];
        $this->assertTrue(PayrollFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(7, $matches);
    }

    public function testMatchesPattern(): void {
        $this->assertTrue(PayrollFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(PayrollFileService::matchesPattern($invalidFilename));
    }

    public function testPayrollFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $service = new PayrollFileService($this->testFile);
        $service->process();

        $this->assertEquals('9', $service->getMonth());
        $this->assertEquals('2023', $service->getYear());
    }

    public function testFileDispatcherPayrollFileServiceProcessing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);
    }
}

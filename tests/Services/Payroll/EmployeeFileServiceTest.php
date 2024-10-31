<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services\Payroll;

use App\Helper\FileDispatcher;
use App\Services\Payroll\EmployeeFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class EmployeeFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../../.samples/20542_10_2024_Brutto_Netto_00001_AA0.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(EmployeeFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(10, $matches);
    }

    public function testMatchesPattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(EmployeeFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(EmployeeFileService::matchesPattern($invalidFilename));
    }

    public function testPayrollFileServiceProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new EmployeeFileService($this->testFile);
        $service->Process();

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

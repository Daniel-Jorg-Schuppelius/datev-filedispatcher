<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeePayrollFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services\Payroll;

use App\Helper\FileDispatcher;
use App\Services\Payroll\EmployeePayrollFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class EmployeePayrollFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../../.samples/20542_00001_Wegner_Regina_09_2023_Brutto_Netto_O04.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function test_pattern_matching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(EmployeePayrollFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        // 6 numerische + 4 benannte Gruppen (tenant, employee, month, year) + Gesamttreffer
        $this->assertCount(11, $matches);
        $this->assertSame('00001', $matches['employee']);
    }

    public function test_matches_pattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(EmployeePayrollFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(EmployeePayrollFileService::matchesPattern($invalidFilename));
    }

    public function test_payroll_file_service_processing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new EmployeePayrollFileService($this->testFile);
        $service->process();

        $this->assertEquals('9', $service->getMonth());
        $this->assertEquals('2023', $service->getYear());
    }

    public function test_file_dispatcher_payroll_file_service_processing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);
    }
}

<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentOverviewsFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services\Payroll;

use App\Helper\FileDispatcher;
use App\Services\Payroll\PaymentOverviewsFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class PaymentOverviewsFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../../.samples/40699_09_2023_Übersicht_Zahlungen_R03.pdf');
        $this->apiDisabled = true; // API is disabled
    }

    public function test_pattern_matching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(PaymentOverviewsFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(8, $matches);
    }

    public function test_matches_pattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(PaymentOverviewsFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(PaymentOverviewsFileService::matchesPattern($invalidFilename));
    }

    public function test_payroll_file_service_processing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        } elseif (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $service = new PaymentOverviewsFileService($this->testFile);
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

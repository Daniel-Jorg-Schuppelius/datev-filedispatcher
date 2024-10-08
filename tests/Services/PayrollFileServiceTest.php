<?php

namespace Tests\Services;

use App\Helper\FileDispatcher;
use PHPUnit\Framework\TestCase;

class PayrollFileServiceTest extends TestCase {
    protected string $testFile;

    protected function setUp(): void {
        // Pfad zur Testdatei
        $this->testFile = __DIR__ . '/../../.samples/20542_00001_Wegner_Regina_09_2023_Brutto_Netto_O04.pdf';
    }

    public function testPayrollFileServiceProcessing(): void {
        // Sicherstellen, dass die Testdatei existiert
        $this->assertFileExists($this->testFile);

        // Verarbeite die Datei über den FileDispatcher
        FileDispatcher::processFile($this->testFile);
    }
}

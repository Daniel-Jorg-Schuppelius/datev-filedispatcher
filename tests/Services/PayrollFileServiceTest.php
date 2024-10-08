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

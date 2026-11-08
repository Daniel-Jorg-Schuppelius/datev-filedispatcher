<?php
/*
 * Created on   : Tue Sep 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DuplicateNumberProcessFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services;

use App\PreProcessServices\DuplicateNumberProcessFileService;
use CommonToolkit\Helper\FileSystem\File;
use PHPUnit\Framework\TestCase;

class DuplicateNumberProcessFileServiceTest extends TestCase {
    protected string $testDir;

    public function __construct($name) {
        parent::__construct($name);
        $this->testDir = __DIR__ . '/../../.samples/';
    }

    public function test_matches_pattern(): void {
        $filename = $this->testDir . 'test (1).txt';
        File::write($filename, 'dummy');

        $this->assertTrue(DuplicateNumberProcessFileService::matchesPattern($filename));
        $this->assertFalse(DuplicateNumberProcessFileService::matchesPattern($this->testDir . 'normal.txt'));

        File::delete($filename);
    }

    public function test_pre_process_removes_duplicate_number(): void {
        $filename = $this->testDir . 'example (2).pdf';
        $expected = $this->testDir . 'example.pdf';

        File::write($filename, 'pdfdata');
        if (File::exists($expected)) {
            File::delete($expected);
        }

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($expected));
        $this->assertFalse(File::exists($filename));

        File::delete($expected);
    }

    public function test_multiple_numbers_removed(): void {
        $filename = $this->testDir . 'another (1) (2).docx';
        $expected = $this->testDir . 'another (1).docx'; // nur letzte Zahl entfernt

        File::write($filename, 'docx');
        if (File::exists($expected)) {
            File::delete($expected);
        }

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($expected));
        $this->assertFalse(File::exists($filename));

        File::delete($expected);
    }

    public function test_no_change_if_no_pattern(): void {
        $filename = $this->testDir . 'simplefile.txt';

        File::write($filename, 'textdata');

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($filename));

        File::delete($filename);
    }

    public function test_copy_pattern(): void {
        $filename = $this->testDir . 'dokument - Kopie (2).docx';
        $expected = $this->testDir . 'dokument.docx';

        File::write($filename, 'docxdata');
        if (File::exists($expected)) {
            File::delete($expected);
        }

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($expected));
        $this->assertFalse(File::exists($filename));

        File::delete($expected);
    }

    public function test_number_not_at_end_is_not_affected(): void {
        $filename = $this->testDir . 'test (1) 2023.txt';
        $expected = $filename; // darf sich nicht ändern

        File::write($filename, 'dummy');

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($expected));
        File::delete($expected);
    }
}

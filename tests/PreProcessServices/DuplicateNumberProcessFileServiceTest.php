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

    public function testMatchesPattern(): void {
        $filename = $this->testDir . 'test (1).txt';
        File::write($filename, 'dummy');

        $this->assertTrue(DuplicateNumberProcessFileService::matchesPattern($filename));
        $this->assertFalse(DuplicateNumberProcessFileService::matchesPattern($this->testDir . 'normal.txt'));

        File::delete($filename);
    }

    public function testPreProcessRemovesDuplicateNumber(): void {
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

    public function testMultipleNumbersRemoved(): void {
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

    public function testNoChangeIfNoPattern(): void {
        $filename = $this->testDir . 'simplefile.txt';

        File::write($filename, 'textdata');

        $service = new DuplicateNumberProcessFileService($filename);
        $service->preProcess();

        $this->assertTrue(File::exists($filename));

        File::delete($filename);
    }

    public function testCopyPattern(): void {
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
}
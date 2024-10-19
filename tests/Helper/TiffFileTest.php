<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use App\Helper\FileSystem\File;
use App\Helper\FileSystem\FileTypes\TifFile;
use PHPUnit\Framework\TestCase;

class TiffFileTest extends TestCase {

    private $testFile;

    protected function setUp(): void {
        $this->testFile = realpath(__DIR__ . '/../../.samples/fakejpg.tiff');
    }

    protected function tearDown(): void {
        File::delete($this->testFile);
        if (!File::exists($this->testFile)) {
            copy($this->testFile . '.bak', $this->testFile);
        }
    }

    public function testMimeType() {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('image/jpeg', $mimeType);
    }

    public function testConvertToTiff() {
        $tiffFile = TifFile::repair($this->testFile);
        $this->assertFileExists($tiffFile);
        $this->assertEquals('image/tiff', File::mimeType($tiffFile));
        File::delete($this->testFile);
        if (!File::exists($this->testFile)) {
            copy($this->testFile . '.bak', $this->testFile);
        }
    }

    public function testConvertToPDF() {
        $pdfFile = str_replace('.tiff', '.pdf', $this->testFile); // Ziel PDF-Dateiname

        TifFile::convertToPdf($this->testFile);

        $this->assertTrue(File::exists($pdfFile), "Das PDF wurde nicht erfolgreich erstellt.");

        if (!File::exists($this->testFile)) {
            copy($this->testFile . '.bak', $this->testFile);
        }

        $this->assertTrue(File::exists($this->testFile), "Die ursprüngliche TIFF-Datei wurde nicht korrekt wiederhergestellt.");

        File::delete($pdfFile);
    }

    public function testMerge() {
        $tiffFiles = [
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_1.tif'),
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_2.tif')
        ];
        $bakTiffFiles = [
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_1.tif.bak'),
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_2.tif.bak')
        ];

        for ($i = 0; $i < count($tiffFiles); $i++) {
            if (!$tiffFiles[$i] && File::exists($bakTiffFiles[$i])) {
                File::copy($bakTiffFiles[$i], str_replace('.bak', '', $bakTiffFiles[$i]));
                $tiffFiles[$i] = realpath(str_replace('.bak', '', $bakTiffFiles[$i]));
            }
        }


        $mergedFile = __DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081.tif';
        $pdfFile = __DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081.pdf';

        File::delete($mergedFile);
        File::delete($pdfFile);


        TifFile::merge($tiffFiles, $mergedFile);

        $this->assertFileExists($mergedFile);

        TifFile::convertToPdf(realpath($mergedFile));

        File::delete($mergedFile);
        File::delete($pdfFile);

        foreach ($tiffFiles as $tiffFile) {
            if (!File::exists($tiffFile)) {
                copy($tiffFile . '.bak', $tiffFile);
            }
        }
    }
}

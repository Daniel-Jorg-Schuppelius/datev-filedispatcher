<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TiffPreProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Services;

use App\Helper\FileDispatcher;
use CommonToolkit\Helper\FileSystem\File;
use App\PreProcessServices\TiffPreProcessFileService;
use Tests\Endpoints\DocumentManagement\DocumentTest;

class TiffPreProcessFileServiceTest extends DocumentTest {
    public function __construct($name) {
        parent::__construct($name);
        // Pfad zur Testdatei
        $this->testFile = realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_1.tif');
        // $this->testFile = realpath(__DIR__ . '/../../.samples/235309 - BvFA ESt-Bescheid 2023 - S25C-924070208150_1.tif');
        $this->apiDisabled = true; // API is disabled
    }

    public function testPatternMatching() {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $matches = [];
        $this->assertTrue(TiffPreProcessFileService::matchesPattern($this->testFile, $matches));
        $this->assertIsArray($matches);
        $this->assertCount(3, $matches);
    }

    public function testMatchesPattern(): void {
        if (empty($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $this->assertTrue(TiffPreProcessFileService::matchesPattern($this->testFile));

        $invalidFilename = 'some_invalid_file_name.txt';
        $this->assertFalse(TiffPreProcessFileService::matchesPattern($invalidFilename));
    }

    public function testTiffPreProcessFileServicePreProcessing() {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $tiffFiles = [
            $this->testFile,
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_2.tif')
        ];
        $bakTiffFiles = [
            $this->testFile . '.bak',
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

        $service = new TiffPreProcessFileService($this->testFile);
        $service->preProcess();
        $this->assertFileExists(realpath($pdfFile));

        File::delete($mergedFile);
        File::delete($pdfFile);

        foreach ($tiffFiles as $tiffFile) {
            if (!File::exists($tiffFile)) {
                copy($tiffFile . '.bak', $tiffFile);
            }
        }
    }

    public function testFileDispatcherTiffPreProcessFileServiceProcessing(): void {
        if ($this->apiDisabled) {
            $this->markTestSkipped('API is disabled');
        }

        $tiffFiles = [
            $this->testFile,
            realpath(__DIR__ . '/../../.samples/235310 - BvFA ESt-Bescheid 2023 - S25C-924061909081_2.tif')
        ];
        $bakTiffFiles = [
            $this->testFile . '.bak',
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

        $this->assertFileExists($this->testFile);

        FileDispatcher::processFile($this->testFile);

        File::delete($mergedFile);
        File::delete($pdfFile);

        foreach ($tiffFiles as $tiffFile) {
            if (!File::exists($tiffFile)) {
                copy($tiffFile . '.bak', $tiffFile);
            }
        }
    }
}

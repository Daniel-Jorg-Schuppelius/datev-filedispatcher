<?php
/*
 * Created on   : Fri Oct 18 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TiffFilePreProcessService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\PreProcessFileServiceAbstract;
use App\Helper\FileSystem\Files;
use App\Helper\FileSystem\FileTypes\TifFile;

class TiffPreProcessFileService extends PreProcessFileServiceAbstract {
    // 000000 - ABC Testdokument - 2022_1.tif(f)
    //                              1         2           (3)
    protected const PATTERN = '/^([0-9]+) - (.+?)(?: - (\d{4}_\d+))?\.tif{1,2}$/i';
    // 000000 - ABC Testdokument - 2022_1_1.tif(f)
    //                                        1         2           (3)         4
    protected const SAME_FILE_PATTERN = '/^([0-9]+) - (.+?)(?: - (\d{4}_\d+))?(_\d+)\.tif{1,2}$/i';

    protected const FILE_EXTENSION_PATTERN = "/tif{1,2}$/i";

    protected const DATEV_MORE_THAN_ONE_PAGE_BASENAME_PATTERN = "/^(.+?)(_\d+(_\d+)?)?\.tif{1,2}$/i";
    protected const DATEV_MORE_THAN_ONE_PAGE_EXTENSION_PATTERN = "/tif{1,2}\((\d+)\)$/i";

    protected function extractDataFromFilename(): void {
        $matches = $this->getMatches();

        $this->setPropertiesFromDMS($matches[1]);
    }

    public function preProcess(): bool {
        $matches = [];
        if (preg_match(self::FILE_EXTENSION_PATTERN, $this->document->getExtension())) {
            $this->logger->info("Kein Preprocessing durch diesen PreProccessingService erforderlich für die Datei: {$this->filename}");
            return false;
        }

        if (preg_match(self::DATEV_MORE_THAN_ONE_PAGE_EXTENSION_PATTERN, $this->document->getExtension(), $matches)) {
            $this->logger->info("Mehrseitige TIFF-Dateien erkannt für die Datei: {$this->filename}");

            $tiffFiles = Files::get(dirname($this->filename), false, [], self::SAME_FILE_PATTERN);

            $istFileCount = count($tiffFiles);
            $sollFileCount = (int)$matches[1];
            if ($istFileCount != $sollFileCount) {
                $this->logger->warning("Anzahl der TIFF-Dateien stimmt nicht überein: {$this->filename}. Vorverarbeitung abgebrochen, warte auf weitere Dateien.(Ist: $istFileCount, Soll: $sollFileCount)");
                return true;
            }

            if (!empty($tiffFiles)) {
                $this->logger->info("Mehrseitige TIFF-Dateien gefunden: {$this->filename}");
                $fileNameMatches = [];
                preg_match(self::DATEV_MORE_THAN_ONE_PAGE_BASENAME_PATTERN, basename($this->filename), $fileNameMatches);
                $outputFilePath = dirname($this->filename) . DIRECTORY_SEPARATOR . $fileNameMatches[1] . '.tif';

                TifFile::merge($tiffFiles, $outputFilePath);
                TifFile::convertToPdf($outputFilePath);
            }
        }

        $this->logger->info("Preprocessing der TIFF-Datei abgeschlossen: {$this->filename}");

        return true;
    }
}
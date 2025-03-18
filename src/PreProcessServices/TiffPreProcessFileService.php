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

use App\Contracts\Abstracts\FileServices\PreProcessFileServiceAbstract;
use CommonToolkit\Helper\FileSystem\Files;
use CommonToolkit\Helper\FileSystem\FileTypes\TifFile;

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

    protected function extractDataFromFile(): void {
        self::$logger->info("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();

        $this->setPropertiesFromDMS($matches[1]);
    }

    public function preProcess(): bool {
        self::$logger->info("Preprocessing der TIFF-Datei: {$this->file}");

        $matches = [];
        if (preg_match(self::FILE_EXTENSION_PATTERN, $this->document->getExtension())) {
            self::$logger->info("Kein Preprocessing durch diesen PreProccessingService erforderlich für die Datei: {$this->file}");
            return true;
        }

        if (preg_match(self::DATEV_MORE_THAN_ONE_PAGE_EXTENSION_PATTERN, $this->document->getExtension(), $matches)) {
            self::$logger->info("Mehrseitige TIFF-Dateien erkannt für die Datei: {$this->file}");
            $fileMatches = [];
            preg_match(self::DATEV_MORE_THAN_ONE_PAGE_BASENAME_PATTERN, basename($this->file), $fileMatches);

            $tiffFiles = Files::get(dirname($this->file), false, ["tif", "tiff"], null, $fileMatches[1]);

            $istFileCount = count($tiffFiles);
            $sollFileCount = (int)$matches[1];
            if ($istFileCount != $sollFileCount) {
                self::$logger->warning("Anzahl der TIFF-Dateien stimmt nicht überein: {$this->file}. Vorverarbeitung abgebrochen, warte auf weitere Dateien.(Ist: $istFileCount, Soll: $sollFileCount)");
                return false;
            }

            if (!empty($tiffFiles)) {
                self::$logger->info("Mehrseitige TIFF-Dateien gefunden: {$this->file}");
                $outputFilePath = dirname($this->file) . DIRECTORY_SEPARATOR . $fileMatches[1] . '.tif';

                TifFile::merge($tiffFiles, $outputFilePath);
                TifFile::convertToPdf($outputFilePath);
            }
        }

        self::$logger->info("Preprocessing der TIFF-Datei abgeschlossen: {$this->file}");

        return true;
    }
}

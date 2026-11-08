<?php
/*
 * Created on   : Fri Oct 18 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TiffPreProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\PreProcessFileServiceAbstract;
use App\Enums\PreProcessResult;
use CommonToolkit\Helper\FileSystem\{File, Files};
use CommonToolkit\Helper\FileSystem\FileTypes\TifFile;
use RuntimeException;

/**
 * Führt mehrseitige TIFF-Exporte aus dem DMS (eine Datei je Seite) zu einem PDF zusammen.
 * Einseitige TIFFs wandelt die generische Vorverarbeitung des FileDispatchers um.
 */
class TiffPreProcessFileService extends PreProcessFileServiceAbstract {
    // 000000 - ABC Testdokument - 2022_1.tif(f)
    // Gruppe 1: DMS-Dokumentnummer, Gruppe 2: Bezeichnung, Gruppe 3 (optional): Jahr_Nummer
    protected const PATTERN = '/^([0-9]+) - (.+?)(?: - (\d{4}_\d+))?\.tif{1,2}$/i';

    /** Dateiname ohne Seitennummer, z. B. "000000 - ABC Testdokument" aus "000000 - ABC Testdokument_1.tif" */
    protected const DATEV_MORE_THAN_ONE_PAGE_BASENAME_PATTERN = "/^(.+?)(_\d+(_\d+)?)?\.tif{1,2}$/i";

    /** Endung eines mehrseitigen TIFFs im DMS mit der Seitenzahl, z. B. "tif(3)" */
    protected const DATEV_MORE_THAN_ONE_PAGE_EXTENSION_PATTERN = "/tif{1,2}\((\d+)\)$/i";

    protected function extractDataFromFile(): void {
        $this->logInfo("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();

        $this->setPropertiesFromDMS($matches[1]);
    }

    public function preProcess(): PreProcessResult {
        $this->logInfo("Preprocessing der TIFF-Datei: {$this->file}");

        // Der Konstruktor bricht ab, wenn kein DMS-Dokument ermittelt werden kann.
        $document = $this->document;
        if (is_null($document)) {
            self::logErrorAndThrow(RuntimeException::class, "Kein DMS-Dokument zur Datei gesetzt: {$this->file}");
        }

        $extension = $document->getExtension() ?? '';

        $pageMatches = [];
        if (!preg_match(self::DATEV_MORE_THAN_ONE_PAGE_EXTENSION_PATTERN, $extension, $pageMatches)) {
            $this->logInfo("Kein mehrseitiges TIFF (DMS-Endung '$extension'), die Umwandlung übernimmt die generische Vorverarbeitung: {$this->file}");
            return PreProcessResult::Skipped;
        }

        $this->logInfo("Mehrseitige TIFF-Dateien erkannt für die Datei: {$this->file}");
        $fileMatches = [];
        if (!preg_match(self::DATEV_MORE_THAN_ONE_PAGE_BASENAME_PATTERN, $this->getFilename(), $fileMatches)) {
            $this->logWarning("Basisname der mehrseitigen TIFF-Datei konnte nicht ermittelt werden: {$this->file}");
            return PreProcessResult::Abort;
        }
        $baseName = $fileMatches[1];

        $tiffFiles = Files::get($this->getFolder(), false, ["tif", "tiff"], null, $baseName);

        $actualPageCount = count($tiffFiles);
        $expectedPageCount = (int) $pageMatches[1];
        if ($actualPageCount !== $expectedPageCount) {
            $this->logInfo("Anzahl der TIFF-Dateien stimmt nicht überein: {$this->file}. Warte auf weitere Seiten (Ist: $actualPageCount, Soll: $expectedPageCount).");
            return PreProcessResult::Abort;
        }

        $outputFilePath = $this->getFolder() . DIRECTORY_SEPARATOR . $baseName . '.tif';

        return $this->mergeExclusively($baseName, $outputFilePath);
    }

    /**
     * Fasst die Seiten unter einem exklusiven Lock zusammen. Jede Seite löst einen eigenen
     * Prozess aus, nur einer davon darf zusammenführen.
     */
    private function mergeExclusively(string $baseName, string $outputFilePath): PreProcessResult {
        $lockFile = $outputFilePath . '.lock';

        $lockHandle = fopen($lockFile, 'c');
        if ($lockHandle === false) {
            $this->logWarning("Lock-Datei konnte nicht erstellt werden: {$lockFile}");
            return PreProcessResult::Abort;
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $this->logInfo("Datei wird bereits von einem anderen Prozess verarbeitet: {$this->file}");
            fclose($lockHandle);
            return PreProcessResult::Abort;
        }

        try {
            // Nochmal prüfen, ob die Dateien noch existieren (könnten bereits verarbeitet sein)
            $tiffFiles = Files::get($this->getFolder(), false, ["tif", "tiff"], null, $baseName);
            if (empty($tiffFiles)) {
                $this->logInfo("TIFF-Dateien wurden bereits von einem anderen Prozess verarbeitet: {$this->file}");
                return PreProcessResult::Abort;
            }

            $this->logInfo("Führe mehrseitige TIFF-Dateien zusammen: {$this->file}");
            TifFile::merge($tiffFiles, $outputFilePath);
            TifFile::convertToPdf($outputFilePath);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            if (File::exists($lockFile)) {
                File::delete($lockFile);
            }
        }

        $this->logInfo("Preprocessing der TIFF-Datei abgeschlossen: {$this->file}");

        return PreProcessResult::Done;
    }
}

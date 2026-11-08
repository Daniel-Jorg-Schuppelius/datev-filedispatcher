<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RenamePreProcessFileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices;

use App\Enums\PreProcessResult;
use CommonToolkit\Helper\FileSystem\File;

/**
 * Vorverarbeitung, die nur den Dateinamen bereinigt. Umbenannt wird ausschließlich
 * der Dateiname, das Verzeichnis bleibt unangetastet. Die umbenannte Datei löst ein
 * neues Dateiereignis aus und durchläuft den Dispatcher erneut.
 */
abstract class RenamePreProcessFileServiceAbstract extends PreProcessFileServiceAbstract {
    /** Ergebnis, wenn die Zieldatei bereits existiert. Done = mit dem bisherigen Namen weiterverarbeiten. */
    protected const RESULT_IF_TARGET_EXISTS = PreProcessResult::Abort;

    private ?string $newFilename = null;

    /**
     * Neuer Dateiname ohne Pfad oder null, wenn nichts umzubenennen ist.
     */
    abstract protected function buildNewFilename(): ?string;

    protected function extractDataFromFile(): void {
        $this->logInfo("Prüfe Dateinamen auf Bereinigung: {$this->file}");
        $this->newFilename = $this->buildNewFilename();
    }

    public function preProcess(): PreProcessResult {
        $newFilename = $this->newFilename;
        if (is_null($newFilename) || $newFilename === '' || $newFilename === $this->getFilename()) {
            $this->logInfo("Keine Bereinigung des Dateinamens erforderlich: {$this->file}");
            return PreProcessResult::Skipped;
        }

        $newFile = $this->getFolder() . DIRECTORY_SEPARATOR . $newFilename;
        if (File::exists($newFile)) {
            $this->logWarning("Zieldatei existiert bereits, Datei wird nicht umbenannt: $newFile");
            return static::RESULT_IF_TARGET_EXISTS;
        }

        File::rename($this->file, $newFile);
        $this->logInfo("Dateiname bereinigt: {$this->file} -> $newFile");

        return PreProcessResult::Done;
    }
}

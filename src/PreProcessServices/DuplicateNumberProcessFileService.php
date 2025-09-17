<?php
/*
 * Created on   : Tue Sep 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DuplicateNumberProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\PreProcessFileServiceAbstract;
use CommonToolkit\Helper\FileSystem\File;

class DuplicateNumberProcessFileService extends PreProcessFileServiceAbstract {
    // Beispiele:
    // test (1).txt -> test.txt
    // abc (23).pdf -> abc.pdf
    // dokument - Kopie (2).docx -> dokument.docx
    // dokument - Kopie (2) (3).docx -> dokument - Kopie (2).docx
    protected const PATTERN = '/( - (Kopie|Copy)(?: \(\d+\))?)|(\s\(\d+\)(?=\.[^.]+$))/';
    private string $newFile;

    protected function extractDataFromFile(): void {
        $this->logInfo("Suche Nummernzusätze im Dateinamen: {$this->file}");
        $this->newFile = preg_replace(self::PATTERN, '', $this->file);
    }

    public function preProcess(): bool {
        if ($this->newFile !== $this->file && !File::exists($this->newFile)) {
            File::rename($this->file, $this->newFile);
            $this->logInfo("Dateiname bereinigt: {$this->file} -> {$this->newFile}");
        } elseif (File::exists($this->newFile)) {
            $this->logWarning("Zieldatei existiert bereits, Datei wird nicht umbenannt: {$this->newFile}");
        } else {
            $this->logInfo("Kein Nummernzusatz im Dateinamen gefunden: {$this->file}");
        }

        return true;
    }
}
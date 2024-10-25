<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TimeCodeProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\PreProcessFileServiceAbstract;
use App\Helper\FileSystem\File;

class PDFNameProcessFileService extends PreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021132856 Abc 001293.pdf
    // oder
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021_132856.pdf
    //                                   1
    protected const PATTERN = '/\s(BvFA|BaFA|BaM|BvM)\s.*\.pdf$/i';

    protected ?string $existingFilenamePart = null;

    protected function extractDataFromFile(): void {
        $this->logger->info("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();

        if (isset($matches[1])) {
            $this->existingFilenamePart = $matches[1] . " ";
        }
    }

    public function preProcess(): bool {
        if (!is_null($this->existingFilenamePart)) {
            File::rename($this->file, str_replace($this->existingFilenamePart, "", $this->file));
        }

        return true;
    }
}

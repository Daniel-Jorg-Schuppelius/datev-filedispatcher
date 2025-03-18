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
use CommonToolkit\Helper\FileSystem\File;
use DateTime;

class PDFTimeCodeProcessFileService extends PreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021132856 Abc 001293.pdf
    // oder
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021_132856.pdf
    //                                   1
    protected const PATTERN = '/(\d{8}(?:_\d{6}|\d{6})).*\.pdf$/i';

    private ?DateTime $fileDate = null;

    protected function extractDataFromFile(): void {
        $this->logInfo("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();
        $date = false;

        if (strpos($matches[1], '_') !== false) {
            $date = DateTime::createFromFormat('Ymd_His', $matches[1]);
        } else {
            $date = DateTime::createFromFormat('YmdHis', $matches[1]);
        }

        if ($date) {
            $this->fileDate = $date;
        }
    }

    public function preProcess(): bool {
        $matches = $this->getMatches();

        if (!is_null($this->fileDate) && $this->fileDate->format(strpos($matches[1], '_') !== false ? 'Ymd_His' : 'YmdHis') === $matches[1]) {
            File::rename($this->file, str_replace(" - " . $matches[1], "", $this->file));
        }

        return true;
    }
}

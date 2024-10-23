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
use DateTime;

class TimeCodeProcessFileService extends PreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021132856_5.tif
    //                                   1
    protected const PATTERN = '/ - (\d{14})(?=_)/';

    private ?DateTime $fileDate = null;

    protected function extractDataFromFile(): void {
        $this->logger->info("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();

        $this->fileDate = DateTime::createFromFormat('YmdHis', $matches[1]);
    }

    public function preProcess(): bool {
        $matches = $this->getMatches();

        if (!is_null($this->fileDate) && $this->fileDate && $this->fileDate->format('YmdHis') === $matches[1]) {
            File::rename($this->file, str_replace($matches[1], "", $this->file));
            return false;
        }

        return true;
    }
}

<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PDFScannerCodeProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\RenamePreProcessFileServiceAbstract;

class PDFScannerCodeProcessFileService extends RenamePreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - S25C-924070208151.pdf
    // Gruppe 1: Code des Scanners, wird samt " - " aus dem Dateinamen entfernt
    protected const PATTERN = '/ - ([A-Za-z0-9]{4,}-\d{12})\.pdf$/i';

    protected function buildNewFilename(): ?string {
        return str_replace(' - ' . $this->getMatches()[1], '', $this->getFilename());
    }
}

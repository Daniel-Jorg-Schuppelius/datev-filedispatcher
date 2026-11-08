<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PDFTimeCodeProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\RenamePreProcessFileServiceAbstract;
use DateTime;

class PDFTimeCodeProcessFileService extends RenamePreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021132856 Abc 001293.pdf
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021_132856.pdf
    // Gruppe 1: Zeitstempel, wird samt " - " aus dem Dateinamen entfernt
    protected const PATTERN = '/(\d{8}(?:_\d{6}|\d{6})).*\.pdf$/i';

    protected function buildNewFilename(): ?string {
        $timeCode = $this->getMatches()[1];
        $format = str_contains($timeCode, '_') ? 'Ymd_His' : 'YmdHis';

        // Nur echte Zeitstempel entfernen, keine beliebigen 14-stelligen Nummern.
        $date = DateTime::createFromFormat($format, $timeCode);
        if ($date === false || $date->format($format) !== $timeCode) {
            $this->logDebug("Kein gültiger Zeitstempel im Dateinamen: {$this->file}");
            return null;
        }

        return str_replace(' - ' . $timeCode, '', $this->getFilename());
    }
}

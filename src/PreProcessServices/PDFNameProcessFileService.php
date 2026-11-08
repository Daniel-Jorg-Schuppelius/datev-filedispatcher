<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PDFNameProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\RenamePreProcessFileServiceAbstract;

class PDFNameProcessFileService extends RenamePreProcessFileServiceAbstract {
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021132856 Abc 001293.pdf
    // 000000 - BvFA Feststellungsbescheid 2022 - 20241021_132856.pdf
    // Gruppe 1: Kürzel aus dem DMS, wird aus dem Dateinamen entfernt
    protected const PATTERN = '/\s(BvFA|BaFA|BaM|BvM)\s.*\.pdf$/i';

    protected function buildNewFilename(): ?string {
        $abbreviation = preg_quote($this->getMatches()[1], '/');

        return preg_replace('/\s' . $abbreviation . '\s/', ' ', $this->getFilename(), 1);
    }
}

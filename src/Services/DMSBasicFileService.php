<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Abstracts\DMSFileServiceAbstract;

class DMSBasicFileService extends DMSFileServiceAbstract {
    // 000000 - ABC Testdokument - 2022_1.pdf
    //                              1        2            (3)
    protected const PATTERN = '/^([0-9]+) - (.+?)(?: - (\d{4}_\d+))?\.pdf$/';
    // protected const PATTERN = '/\/([0-9]{1,}) - (.+)\.pdf$/i';

    protected function extractDataFromFilename(): void {
        $matches = $this->getMatches();

        $this->setPropertiesFromDMS($matches[1]);
    }
}
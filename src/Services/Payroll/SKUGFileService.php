<?php
/*
 * Created on   : Fri Dec 20 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SKUGFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SKUGFileService  extends PayrollFileServiceAbstract {
    // 00000_00_0000_Leistungsantr_Saison_Kug_A01.pdf
    // 00000_00_0000_Anlage_LA_Saison_Kug_A01.pdf
    //                                        1               2              3     4                        5
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_(\w+)_Saison_Kug_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "SKUG-Antrag";

        return "{$documentType}-{$matches[4]}.pdf";
    }
}

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

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SEPAPayrollFileService extends PayrollFileServiceAbstract {
    // SEPA-00000-2023_05-C01-Lohn_Gehalt.xml
    //                                             1              2               3                  4
    protected const PATTERN = '/^SEPA-(?<tenant>\d{5})-(?<year>\d{4})_(?<month>\d{2})-[A-Z0-9]{2,3}-(.+)+\.xml$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "SEPA";

        return "{$documentType} - {$matches[4]}.xml";
    }
}

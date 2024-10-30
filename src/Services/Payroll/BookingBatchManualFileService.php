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

class BookingBatchManualFileService  extends PayrollFileServiceAbstract {
    // LUG_Buchungsbelege_000000_00000_202312.CSV
    // LUG_Buchungsbelege_000000_00000_202401.txt
    //                                                  1                  2              3              4       5
    protected const PATTERN = '/^LUG_Buchungsbelege_(\d{6,7})_(?<tenant>\d{5})_(?<year>\d{4})(?<month>\d{2})\.(csv|txt)$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "Lohnbuchungsbelege";

        return "{$documentType}.{$matches[5]}";
    }
}

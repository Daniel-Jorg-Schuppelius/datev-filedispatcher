<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class BookingBatchFileService extends PayrollFileServiceAbstract {
    // DTVF_000000_00000_LOHNBUCHUNGEN_LUG_202305_20230605_1234.csv
    //                                    1                  2                                3              4       5       6
    protected const PATTERN = '/^DTVF_(\d{6,7})_(?<tenant>\d{5})_LOHNBUCHUNGEN_LUG_(?<year>\d{4})(?<month>\d{2})_(\d{8})_(\d{4})\.csv$/i';

    protected function getDestinationFilename(): string {
        $documentType = "DTVF_Lohnbuchungen";

        return "{$documentType}.csv";
    }
}

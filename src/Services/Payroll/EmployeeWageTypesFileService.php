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

class EmployeeWageTypesFileService extends PayrollFileServiceAbstract {
    // 000000_00000_ABC GbR_03_2024.xlsm
    //                               1                  2    3               4              5
    protected const PATTERN = '/^(\d{6,7})_(?<tenant>\d{5})_(.+)_(?<month>\d{2})_(?<year>\d{4})\.xlsm$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "Lohnarten";

        return "{$documentType} - {$matches[3]}.xlsm";
    }
}

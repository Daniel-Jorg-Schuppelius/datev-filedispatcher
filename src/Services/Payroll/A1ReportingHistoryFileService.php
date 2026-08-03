<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : A1ReportingHistoryFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class A1ReportingHistoryFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Meldeverlauf_A1_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Meldeverlauf_A1_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Meldeverlauf - A1-Bescheinigung";

        return "{$documentType}.pdf";
    }
}

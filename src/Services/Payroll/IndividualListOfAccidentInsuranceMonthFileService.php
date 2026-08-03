<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IndividualListOfAccidentInsuranceMonthFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class IndividualListOfAccidentInsuranceMonthFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Einzelaufstellung_Unfallvers_(monatlich)_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Einzelaufstellung_Unfallvers_\(monatlich\)_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Einzelaufstellung - Unfallversicherung (monatlich)";

        return "{$documentType}.pdf";
    }
}

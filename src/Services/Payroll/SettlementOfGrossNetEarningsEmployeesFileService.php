<?php
/*
 * Created on   : Wed Dec 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SettlementOfGrossNetEarningsEmployeesFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SettlementOfGrossNetEarningsEmployeesFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_Probe_Div_Mitarbeiter_AA0.pdf
    //                                        1               2              3                                                4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto_Probe_Div_Mitarbeiter_([A-Z0-9]{2,3})\.pdf$/i';


    protected function getDestinationFilename(): string {
        $documentType = "Probeabrechnung_Mitarbeiter";

        return "{$documentType}.pdf";
    }
}
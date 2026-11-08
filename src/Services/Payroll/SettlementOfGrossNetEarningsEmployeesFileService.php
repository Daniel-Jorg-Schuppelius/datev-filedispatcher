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
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Brutto_Netto_Probe_Div_Mitarbeiter' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Probeabrechnung_Mitarbeiter";
}

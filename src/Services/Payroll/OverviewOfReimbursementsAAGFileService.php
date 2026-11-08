<?php
/*
 * Created on   : Wed Jan 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OverviewOfReimbursementsAAGFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class OverviewOfReimbursementsAAGFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Übersicht_DÜ_Erstatt_AAG_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Übersicht_DÜ_Erstatt_AAG' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Übersicht_DÜ_Erstattungen_nach_AAG";
}

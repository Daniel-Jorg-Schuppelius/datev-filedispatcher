<?php
/*
 * Created on   : Tue Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousEvaluationForEmployeesFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class VariousEvaluationForEmployeesFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Div_Auswertungen_Div_Mitarbeiter_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Div_Auswertungen_Div_Mitarbeiter' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Diverse-Auswertungen_Mitarbeiter";
}

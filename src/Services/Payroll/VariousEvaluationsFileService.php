<?php
/*
 * Created on   : Wed Sep 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousEvaluationsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class VariousEvaluationsFileService extends PayrollFileServiceAbstract {
    // 00000_08_2026_Div_Auswertungen_L02.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Div_Auswertungen' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Diverse-Auswertungen";
}

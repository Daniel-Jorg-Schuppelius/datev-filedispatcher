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
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Meldeverlauf_A1' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Meldeverlauf - A1-Bescheinigung";
}

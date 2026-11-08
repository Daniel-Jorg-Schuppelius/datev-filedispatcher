<?php
/*
 * Created on   : Mon Jan 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OverpaymentValuesFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class OverpaymentValuesFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Überzahlungen_Werte_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Überzahlungen_Werte' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Überzahlungen - Werte";
}

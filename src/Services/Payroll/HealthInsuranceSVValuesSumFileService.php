<?php
/*
 * Created on   : Tue Jan 28 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HealthInsuranceSVValuesSumFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class HealthInsuranceSVValuesSumFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_KK_SV_Werte___Summen_v_AA0.pdf
    // 00000_10_2024_KK_SV_Werte___Summen_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'KK_SV_Werte___Summen(?:_v)?' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Krankenkasse_SV-Werte_Summen";
}

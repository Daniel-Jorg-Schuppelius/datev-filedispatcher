<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CashPaymentValuesFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class CashPaymentValuesFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Barauszahlungswerte_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Barauszahlungswerte' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Barauszahlungswerte";
}

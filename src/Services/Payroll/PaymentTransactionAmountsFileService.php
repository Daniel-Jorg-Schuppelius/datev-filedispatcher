<?php
/*
 * Created on   : Wed Nov 20 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTransactionAmountsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PaymentTransactionAmountsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Zahlungsverkehr_Summen_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Zahlungsverkehr_Summen' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Summen_Zahlungsverkehr";
}

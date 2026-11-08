<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentOrderFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PaymentOrderFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Zahlungsverkehr_Summen.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Zahlungsverkehr_Summen\.pdf$/i';

    protected const DOCUMENT_TYPE = "Zahlungsverkehr_Summen";
}

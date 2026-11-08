<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentOverviewsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PaymentOverviewsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Übersicht_Zahlungen_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Übersicht_Zahlungen' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Übersicht_Zahlungen";
}

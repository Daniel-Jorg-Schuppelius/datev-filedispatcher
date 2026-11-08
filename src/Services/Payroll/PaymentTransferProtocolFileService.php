<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTransferProtocolFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PaymentTransferProtocolFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Überweisungsprotokoll_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Überweisungsprotokoll' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Überweisungsprotokoll";
}

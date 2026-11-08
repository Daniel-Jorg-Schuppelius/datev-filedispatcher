<?php
/*
 * Created on   : Wed Feb 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataProtocolPaymentsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class DataProtocolPaymentsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_DÜ_Protok_Überweisungen_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'DÜ_Protok_Überweisungen' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "DÜ-Protokoll_SEPA-Abstimmliste";
}

<?php
/*
 * Created on   : Wed Jan 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollJournalAnnualValuesFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PayrollJournalAnnualValuesFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Lohnjournal_Jahresw_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Lohnjournal_Jahresw' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Jahreswerte_Lohnjournal";
}

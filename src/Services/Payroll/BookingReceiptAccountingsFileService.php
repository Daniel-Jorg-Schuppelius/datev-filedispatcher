<?php
/*
 * Created on   : Mon Jan 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingReceiptAccountingsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class BookingReceiptAccountingsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Buchungsbeleg_Kontennachweis_pro_Mitarbeiter_Div_Mitarbeiter_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Buchungsbeleg_Kontennachweis_pro_Mitarbeiter_Div_Mitarbeiter' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Buchungsbeleg - Kontennachweis pro Mitarbeiter (Div Mitarbeiter)";
}

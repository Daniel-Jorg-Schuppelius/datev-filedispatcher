<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SettlementOfGrossNetRemunerationsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SettlementOfGrossNetRemunerationsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_Bau_00001_AA0.pdf
    //                                        1               2              3                    4           5             6
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto_([A-Za-z]+)_(\d{5})_([A-Z0-9]{2,3})\.pdf$/i';
}

<?php
/*
 * Created on   : Wed Sep 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousEvaluationsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class VariousEvaluationsFileService extends PayrollFileServiceAbstract {
    // 00000_08_2026_Div_Auswertungen_L02.pdf
    //                                        1               2              3                       4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Div_Auswertungen_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Diverse-Auswertungen";

        return "{$documentType}.pdf";
    }
}
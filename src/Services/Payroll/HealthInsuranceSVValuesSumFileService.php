<?php
/*
 * Created on   : Tue Jan 28 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HealthInsuranceSVValuesSumFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class HealthInsuranceSVValuesSumFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_KK_SV_Werte___Summen_v_AA0.pdf
    // 00000_10_2024_KK_SV_Werte___Summen_AA0.pdf
    //                                        1               2              3                                         4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_KK_SV_Werte___Summen(?:_v)?_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Krankenkasse_SV-Werte_Summen";

        return "{$documentType}.pdf";
    }
}

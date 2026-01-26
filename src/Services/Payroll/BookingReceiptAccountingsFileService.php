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
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Buchungsbeleg_Kontennachweis_pro_Mitarbeiter_Div_Mitarbeiter_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Buchungsbeleg - Kontennachweis pro Mitarbeiter (Div Mitarbeiter)";

        return "{$documentType}.pdf";
    }
}

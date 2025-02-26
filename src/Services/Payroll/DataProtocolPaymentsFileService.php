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
    //                                        1               2              3                                     4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_DÜ_Protok_Überweisungen_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "DÜ-Protokoll_SEPA-Abstimmliste";

        return "{$documentType}.pdf";
    }
}
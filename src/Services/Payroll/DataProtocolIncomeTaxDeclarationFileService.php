<?php
/*
 * Created on   : Wed Jan 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataProtocolIncomeTaxDeclarationFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class DataProtocolIncomeTaxDeclarationFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_DÜ_Protok_LSt_Anm_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_DÜ_Protok_LSt_Anm_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "DÜ-Protokoll_Lohnsteuer-Anmeldung";

        return "{$documentType}.pdf";
    }
}

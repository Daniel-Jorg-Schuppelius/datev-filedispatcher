<?php
/*
 * Created on   : Wed Feb 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MasterDataChangeProtocolFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class MasterDataChangeProtocolFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Stammdatenänd_Prot_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Stammdatenänd_Prot_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Stammdatenänderungsprotokoll";

        return "{$documentType}.pdf";
    }
}

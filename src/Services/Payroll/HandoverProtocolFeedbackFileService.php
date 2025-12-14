<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HandoverProtocolFeedbackFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class HandoverProtocolFeedbackFileService extends PayrollFileServiceAbstract {
    // 00000_11_2024_Übern_Prot_Rückm_Verf_AA0.pdf
    //                                        1               2              3                                   4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Übern_Prot_Rückm_Verf_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "Übernahmeprotokoll_Rückmeldeverfahren";

        return "{$documentType}.pdf";
    }
}
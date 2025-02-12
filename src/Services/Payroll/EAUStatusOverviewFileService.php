<?php
/*
 * Created on   : Wed Feb 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EAUStatusOverviewFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class EAUStatusOverviewFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Dialoginhalt_Sammel_AW_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Dialoginhalt_Sammel_AW_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $documentType = "eAU-Statusübersicht";

        return "{$documentType}.pdf";
    }
}

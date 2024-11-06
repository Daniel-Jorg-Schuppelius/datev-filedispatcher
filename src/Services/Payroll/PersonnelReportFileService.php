<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonnelReportFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use APIToolkit\Enums\Month;
use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PersonnelReportFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Personalreport_AA0.pdf
    //                                        1               2              3                                    4                    5             6
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Personalreport_(?<reportmonth>\d{2})_(?<reportyear>\d{4})_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $documentType = "Personalreport";

        $namedMonth = Month::toArray(false, 'de')[(int) $matches["reportmonth"]];

        return "{$documentType} - {$namedMonth} {$matches["reportyear"]}.pdf";
    }
}

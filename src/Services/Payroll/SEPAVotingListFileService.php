<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SEPAVotingListFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use App\Helper\InternalStoreMapper;

class PersonnelCalendarFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Begleitpapiere_Datei_AA0.pdf
    //                                        1               2              3                                              4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Begleitpapiere_Datei_([A-Z0-9]{2,3})\.pdf$/i';


    protected function getDestinationFilename(): string {
        $documentType = "Begleitpapiere";

        return "{$documentType}.pdf";
    }
}
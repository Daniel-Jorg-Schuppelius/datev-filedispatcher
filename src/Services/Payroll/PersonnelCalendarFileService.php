<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\FileServiceAbstract;
use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use App\Helper\FileSystem\File;
use App\Helper\InternalStoreMapper;

class PersonnelCalendarFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Personalkalender_Div_Mitarbeiter_AA0.pdf
    //                                        1               2              3                                              4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Personalkalender_Div_Mitarbeiter_([A-Z0-9]{2,3})\.pdf$/i';


    public function getDestinationFolder(bool $leadingZero = true): ?string {
        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        $subFolder = $this->prepareSubFolder($this->getSubFolder(), false, true);

        return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted);
    }
}

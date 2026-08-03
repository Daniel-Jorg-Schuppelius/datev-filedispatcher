<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IndividualListOfAccidentInsuranceYearFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use App\Helper\InternalStoreMapper;

class IndividualListOfAccidentInsuranceYearFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Einzelaufstellung_Unfallvers_(jährlich)_AA0.pdf
    //                                        1               2              3                           4
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Einzelaufstellung_Unfallvers_\(jährlich\)_([A-Z0-9]{2,3})\.pdf$/i';

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        $subFolder = $this->prepareSubFolder($this->getSubFolder(), false, true);

        $client = $this->client;
        if (is_null($client)) {
            $this->logError("Kein Client gesetzt, der Zielordner für '" . $subFolder . "' kann nicht bestimmt werden.");
            return null;
        }

        return InternalStoreMapper::getInternalStorePath($client, $subFolder, $yearFormatted);
    }

    protected function getDestinationFilename(): string {
        $documentType = "Einzelaufstellung - Unfallversicherung (jährlich)";

        return "{$documentType}.pdf";
    }
}

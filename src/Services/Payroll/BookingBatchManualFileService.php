<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchManualFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class BookingBatchManualFileService extends PayrollFileServiceAbstract {
    // LUG_Buchungsbelege_000000_00000_202312.CSV
    // LUG_Buchungsbelege_000000_00000_202401.txt
    // Gruppe 5: Endung
    protected const PATTERN = '/^LUG_Buchungsbelege_(\d{6,7})_(?<tenant>\d{5})_(?<year>\d{4})(?<month>\d{2})\.(csv|txt)$/i';

    protected const DOCUMENT_TYPE = "Lohnbuchungsbelege";

    protected function getDestinationFilename(): string {
        return static::DOCUMENT_TYPE . ".{$this->getMatches()[5]}";
    }
}

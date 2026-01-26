<?php
/*
 * Created on   : Mon Jan 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FeedbackConfirmationFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class FeedbackConfirmationFileService extends PayrollFileServiceAbstract {
    // Rueckmeldung_00000_10_2024_PL_Antragsbestaetigung.pdf
    //                                                     1               2              3            4
    protected const PATTERN = '/^Rueckmeldung_(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_(?<type>[A-Z]+)_Antragsbestaetigung\.pdf$/i';

    protected function getDestinationFilename(): string {
        $type = $this->matches['type'] ?? "Unbekannt";
        $documentType = "Rückmeldung - Antragsbestätigung ({$type})";

        return "{$documentType}.pdf";
    }
}

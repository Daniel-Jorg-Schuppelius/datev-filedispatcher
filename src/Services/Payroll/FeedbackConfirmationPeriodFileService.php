<?php
/*
 * Created on   : Mon Jan 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FeedbackConfirmationPeriodFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use DateTime;

class FeedbackConfirmationPeriodFileService extends PayrollFileServiceAbstract {
    // Rueckmeldung_04001_20250612_20270611_PL_Antragsbestaetigung.pdf
    //                                                     1                   2                 3            4
    protected const PATTERN = '/^Rueckmeldung_(?<tenant>\d{5})_(?<startdate>\d{8})_(?<enddate>\d{8})_(?<type>[A-Z]+)_Antragsbestaetigung\.pdf$/i';

    protected function extractDataFromFile(): void {
        $matches = $this->getMatches();

        if (array_key_exists("tenant", $matches)) {
            $this->setClients($matches["tenant"]);
        }

        // Extrahiere Jahr und Monat aus dem Startdatum (YYYYMMDD)
        if (array_key_exists("startdate", $matches)) {
            $startDate = DateTime::createFromFormat('Ymd', $matches["startdate"]);
            if ($startDate) {
                $this->setDate((int) $startDate->format('Y'), (int) $startDate->format('m'));
            }
        }
    }

    protected function getDestinationFilename(): string {
        $type = $this->matches['type'] ?? "Unbekannt";
        $startDate = $this->matches['startdate'] ?? "";
        $endDate = $this->matches['enddate'] ?? "";

        $formattedRange = "";
        if (!empty($startDate) && !empty($endDate)) {
            $start = DateTime::createFromFormat('Ymd', $startDate);
            $end = DateTime::createFromFormat('Ymd', $endDate);
            if ($start && $end) {
                $formattedRange = " " . $start->format('d.m.Y') . " - " . $end->format('d.m.Y');
            }
        }

        $documentType = "Rückmeldung - Antragsbestätigung ({$type}){$formattedRange}";

        return "{$documentType}.pdf";
    }
}

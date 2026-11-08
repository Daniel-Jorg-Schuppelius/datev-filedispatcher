<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeeFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class EmployeeFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_01000_AA0.pdf
    // 00000_10_2024_SV_Nachweis_(DEÜV)_00004_AA1.pdf
    // Gruppe 4: Dokumentart, Gruppe 5: Mitarbeiter
    // Generischer Fallback für Mitarbeiter-Auswertungen, daher die niedrigste Priorität:
    // Spezifische Services mit demselben Aufbau werden vorher geprüft.
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . '(.+)_(\d{5})' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const PRIORITY = -100;

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = match ($matches[4]) {
            'Brutto_Netto' => "Entgeltabrechnung",
            'SV_Nachweis_(DEÜV)' => "Sozialversicherungsmeldung",
            'DÜ_Prot_LSt_Bescheinig' => "Lohnsteuerbescheinigung",
            default => $matches[4],
        };

        return "{$documentType}-" . $this->getEmployeeFilenamePart($matches[5]) . '.pdf';
    }
}

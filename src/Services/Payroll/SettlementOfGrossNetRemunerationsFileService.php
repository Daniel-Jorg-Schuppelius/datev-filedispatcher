<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SettlementOfGrossNetRemunerationsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SettlementOfGrossNetRemunerationsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_Bau_00001_AA0.pdf
    // Gruppe 4: Abrechnungsart, z. B. Bau
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Brutto_Netto_([A-Za-z]+)_' . self::EMPLOYEE . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Entgeltabrechnung";

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        return static::DOCUMENT_TYPE . "-{$matches[4]}_" . $this->getEmployeeFilenamePart($matches['employee']) . '.pdf';
    }
}

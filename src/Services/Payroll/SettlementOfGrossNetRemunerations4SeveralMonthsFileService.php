<?php
/*
 * Created on   : Fri Jan 03 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SettlementOfGrossNetRemunerations4SeveralMonthsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use App\Helper\InternalStoreMapper;
use Exception;

class SettlementOfGrossNetRemunerations4SeveralMonthsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_10_2024_Brutto_Netto_00001.pdf
    //                                        1       2       3               4              5                    6
    protected const PATTERN = '/^(?<tenant>\d{5})_(\d{2})_(\d{4})_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto_(\d{5})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $employeeNumber = $matches[6];

        $documentType = "Mehrmonatige_Entgeltabrechnung";

        if (!is_null($this->payrollClient)) {
            $this->logInfo("Client gefunden: {$this->payrollClient->getNumber()}");

            $employees = $this->payrollClient->getEmployees();
            $employee = $employees->getFirstValue('id', $employeeNumber);
            if (!is_null($employee)) {
                $this->logInfo('Mitarbeiter gefunden: ' . $employee->getSurname() . ' ' . $employee->getFirstName());
                return "{$documentType}_{$employeeNumber}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf";
            }

            $this->logError("Mitarbeiter nicht gefunden: {$employeeNumber}");
            throw new Exception("Mitarbeiter nicht gefunden: {$employeeNumber}");
        }

        $this->logError("Client nicht gefunden: {$matches[1]}");
        throw new Exception("Client nicht gefunden: {$matches[1]}");
    }

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        $subFolder = $this->getSubFolder();

        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        $subFolder = $this->prepareSubFolder($subFolder, false, true);

        $this->logger->info("Nutze Jahresablage für den Ordner '" . $subFolder . "'.");
        return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted);
    }
}

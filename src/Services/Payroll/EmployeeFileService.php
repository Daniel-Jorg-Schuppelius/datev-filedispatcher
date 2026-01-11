<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeeFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use Exception;

class EmployeeFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_01000_AA0.pdf
    // 00000_10_2024_SV_Nachweis_(DEÜV)_00004_AA1.pdf
    //                                           1                  2                 3     4         5             6
    protected const PATTERN = '/^(?<tenant>[0-9]{5})_(?<month>[0-9]{2})_(?<year>[0-9]{4})_(.+)_([0-9]{5})_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $employeeNumber = $matches[5];

        match ($matches[4]) {
            'Brutto_Netto' => $documentType = "Entgeltabrechnung",
            'SV_Nachweis_(DEÜV)' => $documentType = "Sozialversicherungsmeldung",
            'DÜ_Prot_LSt_Bescheinig' => $documentType = "Lohnsteuerbescheinigung",
            default => $documentType = $matches[4],
        };

        if (!is_null($this->payrollClient)) {
            $this->logInfo("Client gefunden: {$this->payrollClient->getNumber()}");

            $employees = $this->payrollClient->getEmployees();
            $employee = $employees->getFirstValue('id', $employeeNumber);
            if (!is_null($employee)) {
                $this->logInfo('Mitarbeiter gefunden: ' . $employee->getSurname() . ' ' . $employee->getFirstName());
                return "{$documentType}-{$employeeNumber}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf";
            }

            self::logErrorAndThrow(Exception::class, "Mitarbeiter nicht gefunden: {$employeeNumber}");
        }

        self::logErrorAndThrow(Exception::class, "Client nicht gefunden: {$matches[1]}");
    }
}

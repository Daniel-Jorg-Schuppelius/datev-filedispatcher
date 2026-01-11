<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeePayrollFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use Exception;

class EmployeePayrollFileService extends PayrollFileServiceAbstract {
    // 00000_00000_Client_Client_00_0000_Brutto_Netto_AA0.pdf
    //                                        1       2            3                        4              5                          6
    protected const PATTERN = '/^(?<tenant>\d{5})_(\d{5})_([A-Za-z]+_[A-Za-z]+)_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $employeeNumber = $matches[2];

        $documentType = "Entgeltabrechnung";

        if (!is_null($this->payrollClient)) {
            $this->logInfo("Client gefunden: {$this->payrollClient->getNumber()}");

            $employees = $this->payrollClient->getEmployees();
            if (!is_null($employees)) {
                $employee = $employees->getFirstValue('id', $employeeNumber);
                if (!is_null($employee)) {
                    return "{$documentType}-{$employeeNumber}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf";
                }

                self::logErrorAndThrow(Exception::class, "Mitarbeiter nicht gefunden: {$employeeNumber}");
            }

            self::logErrorAndThrow(Exception::class, "Keine Mitarbeiter für Client: {$this->payrollClient->getNumber()} gefunden");
        }

        self::logErrorAndThrow(Exception::class, "Client nicht gefunden: {$matches[1]}");
    }
}

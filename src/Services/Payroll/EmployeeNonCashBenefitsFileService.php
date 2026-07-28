<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeeNonCashBenefitsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use Exception;

class EmployeeNonCashBenefitsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_BerechSchema_Firmenwagen_00001_AA0.pdf
    //                                        1               2              3            4                   5             6
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_(BerechSchema_[A-Za-z]+)_(\d{5})_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $employeeNumber = $matches[5];

        $payrollClient = $this->payrollClient;
        if (!is_null($payrollClient)) {
            $this->logInfo("Client gefunden: {$payrollClient->getNumber()}");

            $employees = $payrollClient->getEmployees();
            if (!is_null($employees)) {
                $employee = $employees->getFirstValue('id', $employeeNumber);
                if (!is_null($employee)) {
                    $this->logInfo('Mitarbeiter gefunden: ' . $employee->getSurname() . ' ' . $employee->getFirstName());
                    return "{$matches[4]}-{$employeeNumber}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf";
                }

                self::logErrorAndThrow(Exception::class, "Mitarbeiter nicht gefunden: {$employeeNumber}");
            }

            self::logErrorAndThrow(Exception::class, "Keine Mitarbeiter für Client: {$payrollClient->getNumber()} gefunden");
        }

        self::logErrorAndThrow(Exception::class, "Client nicht gefunden: {$matches[1]}");
    }
}

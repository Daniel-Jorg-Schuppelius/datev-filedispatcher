<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollAccountsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;
use Exception;

class PayrollAccountsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Lohnkonto_00001_AA0.pdf
    //                                        1               2              3                 4             5
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_Lohnkonto_(\d{5})_([A-Z0-9]{2,3})\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();
        $employeeNumber = $matches[4];

        $documentType = "Lohnkonto";

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

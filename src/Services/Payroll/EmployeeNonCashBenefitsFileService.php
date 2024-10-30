<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NonCashBenefitsFileService.php
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

        if (!is_null($this->payrollClient)) {
            $this->logInfo("Client gefunden: {$this->payrollClient->getNumber()}");

            $employees = $this->payrollClient->getEmployees();
            $employee = $employees->getFirstValue('id', $matches[5]);
            if (!is_null($employee)) {
                $this->logInfo('Mitarbeiter gefunden: ' . $employee->getSurname() . ' ' . $employee->getFirstName());
                return "{$matches[4]}_{$matches[5]}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf";
            }

            $this->logError("Mitarbeiter nicht gefunden: {$matches[5]}");
            throw new Exception("Mitarbeiter nicht gefunden: {$matches[5]}");
        }

        $this->logError("Client nicht gefunden: {$matches[1]}");
        throw new Exception("Client nicht gefunden: {$matches[1]}");
    }
}

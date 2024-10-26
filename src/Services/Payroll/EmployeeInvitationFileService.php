<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\FileServiceAbstract;
use App\Helper\FileSystem\File;

class EmployeeInvitationFileService extends FileServiceAbstract {
    // Arbeitnehmer online - Einladungsstatus - 000000 - 00000 - Regina Wegner.csv
    //                                                                        1           2      3
    protected const PATTERN = '/^Arbeitnehmer online - Einladungsstatus - (\d{6,7}) - (\d{5}) - (.+)\.csv$/i';

    protected const SUBFOLDER = "02 Entgeltabrechnung";

    protected const DOCUMENT_EMPLOYEE = "Arbeitnehmer online.docx";
    protected const DOCUMENT_EMPLOYER = "AG Arbeitnehmer online.docx";

    protected function extractDataFromFile(): void {
        $matches = $this->getMatches();

        $this->setClient($matches[2]);
    }

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "Arbeitnehmer online - Einladungsstatus";

        return "{$documentType} - {$matches[3]}.csv";
    }

    public function process(): void {
        parent::process();

        $employeeFilename = $this->getFolder() . DIRECTORY_SEPARATOR . self::DOCUMENT_EMPLOYEE;
        $employerFilename = $this->getFolder() . DIRECTORY_SEPARATOR . self::DOCUMENT_EMPLOYER;

        if (File::exists($employeeFilename)) {
            $this->logInfo("Kopiere Anleitung für Arbeitnehmer : $employeeFilename");
            File::copy($employeeFilename, $this->getDestinationFolder() . DIRECTORY_SEPARATOR . self::DOCUMENT_EMPLOYEE);
        }

        if (File::exists($employerFilename)) {
            $this->logInfo("Kopiere Anleitung für Arbeitgeber : $employerFilename");
            File::copy($employerFilename, $this->getDestinationFolder() . DIRECTORY_SEPARATOR . self::DOCUMENT_EMPLOYER);
        }
    }
}
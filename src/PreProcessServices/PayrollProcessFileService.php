<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\PreProcessFileServiceAbstract;
use App\Helper\FileSystem\File;

class PayrollProcessFileService extends PreProcessFileServiceAbstract {
    // 00000_10_2024_Brutto_Netto_01000_AA0.pdf
    // 00000_10_2024_SV_Nachweis_(DEÜV)_00004_AA1.pdf
    //                                  1          2          3     4         5             6
    protected const PATTERN = '/^([0-9]{5})_([0-9]{2})_([0-9]{4})_(.+)_([0-9]{5})_([A-Z0-9]{2,3})\.pdf$/i';
    protected function extractDataFromFile(): void {
        $this->logger->info("Extrahiere Daten aus dem Dateinamen: {$this->file}");
        $matches = $this->getMatches();

        $this->setPropertiesFromDMS($matches[1]);
    }

    public function preProcess(): bool {
        $matches = $this->getMatches();;

        $employee = $this->payrollClient->getEmployees()->getFirstValue('id', $matches[5]);
        if (!is_null($employee)) {
            match ($matches[4]) {
                'Brutto_Netto' => $documentType = "Entgeltabrechnung",
                'SV_Nachweis_(DEÜV)' => $documentType = "Sozialversicherungsmeldung",
                'DÜ_Prot_LSt_Bescheinig' => $documentType = "Lohnsteuerbescheinigung",
                default => $documentType = $matches[4],
            };

            File::rename($this->file, "{$documentType}_{$matches[5]}_{$employee->getSurname()}_{$employee->getFirstName()}.pdf");
        }

        return true;
    }
}

<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeeInvitationFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\FileServiceAbstract;
use CommonToolkit\Helper\FileSystem\File;

class EmployeeInvitationFileService extends FileServiceAbstract {
    // Arbeitnehmer online - Einladungsstatus - 000000 - 00000 - Regina Wegner.csv
    // Gruppe 1: Beraternummer, Gruppe 2: Mandant, Gruppe 3: Name
    protected const PATTERN = '/^Arbeitnehmer online - Einladungsstatus - (\d{6,7}) - (\d{5}) - (.+)\.csv$/i';

    protected const SUBFOLDER = "02 Entgeltabrechnung";

    /** Anleitungen im Eingangsordner, die zusammen mit dem Einladungsstatus abgelegt werden */
    protected const GUIDES = [
        "Arbeitnehmer online.docx" => "Arbeitnehmer",
        "AG Arbeitnehmer online.docx" => "Arbeitgeber",
    ];

    protected function extractDataFromFile(): void {
        $matches = $this->getMatches();

        $this->setClients($matches[2]);
    }

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        $documentType = "Arbeitnehmer online - Einladungsstatus";

        return "{$documentType} - {$matches[3]}.csv";
    }

    public function process(): void {
        parent::process();

        $destinationFolder = $this->requireDestinationFolder();

        foreach (self::GUIDES as $guide => $audience) {
            $source = $this->getFolder() . DIRECTORY_SEPARATOR . $guide;
            if (File::exists($source)) {
                $this->logInfo("Kopiere Anleitung für $audience: $source");
                File::copy($source, $destinationFolder . DIRECTORY_SEPARATOR . $guide);
            }
        }
    }
}

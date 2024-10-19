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

use App\Contracts\Abstracts\FileServices\PeriodicFileServiceAbstract;

class PayrollAccountsFileService extends PeriodicFileServiceAbstract {
    // 00000_10_2024_Lohnkonto_00001_AA0.pdf
    //                               1       2       3                 4             5
    protected const PATTERN = '/^(\d{5})_(\d{2})_(\d{4})_Lohnkonto_(\d{5})_([A-Z0-9]{2,3})\.pdf$/i';
    protected const SUBFOLDER = "02 Entgeltabrechnung";

    protected function extractDataFromFilename(): void {
        $matches = $this->getMatches();

        $this->setClient($matches[1]);
        $this->setDate((int) $matches[3], (int) $matches[2]);
    }
}

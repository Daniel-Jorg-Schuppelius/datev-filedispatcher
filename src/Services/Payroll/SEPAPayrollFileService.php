<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SEPAPayrollFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SEPAPayrollFileService extends PayrollFileServiceAbstract {
    // SEPA-00000-2023_05-C01-Lohn_Gehalt.xml
    // Gruppe 4: Verwendungszweck
    protected const PATTERN = '/^SEPA-(?<tenant>\d{5})-(?<year>\d{4})_(?<month>\d{2})-[A-Z0-9]{2,3}-(.+)\.xml$/i';

    protected const DOCUMENT_TYPE = "SEPA";

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        return static::DOCUMENT_TYPE . "-{$matches[4]}_{$matches['year']}_{$matches['month']}.xml";
    }
}

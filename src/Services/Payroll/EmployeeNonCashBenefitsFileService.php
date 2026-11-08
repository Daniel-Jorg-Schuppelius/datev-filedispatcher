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

class EmployeeNonCashBenefitsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_BerechSchema_Firmenwagen_00001_AA0.pdf
    // Gruppe 4: Berechnungsschema, wird zum Dokumenttyp
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . '(BerechSchema_[A-Za-z]+)_' . self::EMPLOYEE . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected function getDestinationFilename(): string {
        $matches = $this->getMatches();

        return "{$matches[4]}-" . $this->getEmployeeFilenamePart($matches['employee']) . '.pdf';
    }
}

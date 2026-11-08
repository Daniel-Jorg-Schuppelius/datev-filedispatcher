<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IndividualListOfAccidentInsuranceYearFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class IndividualListOfAccidentInsuranceYearFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Einzelaufstellung_Unfallvers_(jährlich)_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Einzelaufstellung_Unfallvers_\(jährlich\)' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Einzelaufstellung - Unfallversicherung (jährlich)";

    protected const FORCE_YEARLY_STORAGE = true;
}

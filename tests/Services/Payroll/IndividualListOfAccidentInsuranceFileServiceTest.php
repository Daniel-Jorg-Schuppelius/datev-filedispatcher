<?php
/*
 * Created on   : Mon Aug 03 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IndividualListOfAccidentInsuranceFileServiceTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Services\Payroll;

use App\Services\Payroll\{IndividualListOfAccidentInsuranceMonthFileService, IndividualListOfAccidentInsuranceYearFileService};
use PHPUnit\Framework\TestCase;

class IndividualListOfAccidentInsuranceFileServiceTest extends TestCase {
    public function test_monthly_pattern_matches_filename(): void {
        $this->assertTrue(IndividualListOfAccidentInsuranceMonthFileService::matchesPattern(
            '27960_12_2025_Einzelaufstellung_Unfallvers_(monatlich)_X09.pdf'
        ));
    }

    public function test_yearly_pattern_matches_filename(): void {
        $this->assertTrue(IndividualListOfAccidentInsuranceYearFileService::matchesPattern(
            '27960_12_2025_Einzelaufstellung_Unfallvers_(jährlich)_X09.pdf'
        ));
    }
}

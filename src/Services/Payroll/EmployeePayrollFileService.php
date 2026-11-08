<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmployeePayrollFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class EmployeePayrollFileService extends PayrollFileServiceAbstract {
    // 00000_00000_Client_Client_00_0000_Brutto_Netto_AA0.pdf
    // Mandant_Mitarbeiter_Name_Monat_Jahr_Brutto_Netto_Kennung
    protected const PATTERN = '/^(?<tenant>\d{5})_' . self::EMPLOYEE . '_([A-Za-z]+_[A-Za-z]+)_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Entgeltabrechnung";
}

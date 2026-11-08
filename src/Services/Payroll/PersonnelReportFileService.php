<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonnelReportFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PersonnelReportFileService extends PayrollFileServiceAbstract {
    // 00000_11_2024_Personalreport_10_2024_AA0.pdf
    // Mandant_Erstellungsmonat_Erstellungsjahr_Personalreport_Monat_Jahr_Kennung
    protected const PATTERN = '/^(?<tenant>\d{5})_(?<creationmonth>\d{2})_(?<creationyear>\d{4})_Personalreport_(?<month>\d{2})_(?<year>\d{4})' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Personalreport";
}

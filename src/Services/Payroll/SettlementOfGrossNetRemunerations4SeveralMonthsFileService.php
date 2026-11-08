<?php
/*
 * Created on   : Fri Jan 03 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SettlementOfGrossNetRemunerations4SeveralMonthsFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SettlementOfGrossNetRemunerations4SeveralMonthsFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_10_2024_Brutto_Netto_00001.pdf
    // Mandant_VonMonat_VonJahr_BisMonat_BisJahr_Brutto_Netto_Mitarbeiter, Ablage im Jahr der Bis-Periode
    protected const PATTERN = '/^(?<tenant>\d{5})_(\d{2})_(\d{4})_(?<month>\d{2})_(?<year>\d{4})_Brutto_Netto_' . self::EMPLOYEE . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Mehrmonatige_Entgeltabrechnung";

    protected const FORCE_YEARLY_STORAGE = true;
}

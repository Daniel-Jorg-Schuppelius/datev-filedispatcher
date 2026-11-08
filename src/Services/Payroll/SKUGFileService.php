<?php
/*
 * Created on   : Fri Dec 20 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SKUGFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SKUGFileService extends PayrollFileServiceAbstract {
    // 00000_00_0000_Leistungsantr_Saison_Kug_A01.pdf
    // 00000_00_0000_Anlage_LA_Saison_Kug_A01.pdf
    // Gruppe 4: Art des Antrags
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . '(\w+)_Saison_Kug' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "SKUG-Antrag";

    protected function getDestinationFilename(): string {
        return static::DOCUMENT_TYPE . "-{$this->getMatches()[4]}.pdf";
    }
}

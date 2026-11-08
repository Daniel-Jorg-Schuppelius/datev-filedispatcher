<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonnelCalendarFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class PersonnelCalendarFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Personalkalender_Div_Mitarbeiter_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Personalkalender_Div_Mitarbeiter' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const FORCE_YEARLY_STORAGE = true;
}

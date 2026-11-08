<?php
/*
 * Created on   : Wed Feb 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EAUStatusOverviewFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class EAUStatusOverviewFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Dialoginhalt_Sammel_AW_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Dialoginhalt_Sammel_AW' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "eAU-Statusübersicht";
}

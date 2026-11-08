<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HandoverProtocolFeedbackFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class HandoverProtocolFeedbackFileService extends PayrollFileServiceAbstract {
    // 00000_11_2024_Übern_Prot_Rückm_Verf_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Übern_Prot_Rückm_Verf' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Übernahmeprotokoll_Rückmeldeverfahren";
}

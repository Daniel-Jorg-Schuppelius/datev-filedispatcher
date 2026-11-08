<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SEPAVotingListFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Abstracts\FileServices\Periodic\PayrollFileServiceAbstract;

class SEPAVotingListFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Begleitpapiere_Datei_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Begleitpapiere_Datei' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Begleitpapiere";
}

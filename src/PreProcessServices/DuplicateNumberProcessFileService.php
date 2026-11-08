<?php
/*
 * Created on   : Tue Sep 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DuplicateNumberProcessFileService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\PreProcessServices;

use App\Contracts\Abstracts\FileServices\RenamePreProcessFileServiceAbstract;
use App\Enums\PreProcessResult;

class DuplicateNumberProcessFileService extends RenamePreProcessFileServiceAbstract {
    // Beispiele:
    // test (1).txt -> test.txt
    // abc (23).pdf -> abc.pdf
    // dokument - Kopie (2).docx -> dokument.docx
    // another (1) (2).docx -> another (1).docx (nur die Nummer direkt vor der Endung)
    protected const PATTERN = '/( - (Kopie|Copy)(?: \(\d+\))?)|(\s\(\d+\)(?=\.[^.]+$))/';

    /** Existiert der bereinigte Name schon, geht die Datei unter ihrem bisherigen Namen weiter. */
    protected const RESULT_IF_TARGET_EXISTS = PreProcessResult::Done;

    protected function buildNewFilename(): ?string {
        // Bei einem preg_replace-Fehler (null) bleibt der Originalname erhalten.
        return preg_replace(self::PATTERN, '', $this->getFilename());
    }
}

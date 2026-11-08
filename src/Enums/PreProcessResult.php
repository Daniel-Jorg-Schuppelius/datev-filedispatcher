<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PreProcessResult.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Enums;

/**
 * Ergebnis eines PreProcessServices. Steuert, wie der FileDispatcher weitermacht.
 */
enum PreProcessResult {
    /** Vorverarbeitung erledigt, die Datei geht an die FileServices. */
    case Done;

    /** Nicht zuständig: nächsten PreProcessService bzw. die generische Vorverarbeitung versuchen. */
    case Skipped;

    /** Verarbeitung abbrechen, die Datei bleibt liegen (z. B. fehlende TIFF-Seiten, paralleler Prozess). */
    case Abort;
}

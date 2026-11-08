<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PeriodicFileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices;

use App\Traits\PeriodicFileServiceTrait;

abstract class PeriodicFileServiceAbstract extends FileServiceAbstract {
    use PeriodicFileServiceTrait;

    /**
     * Periodische Services brauchen eine Jahres- oder Monatsablage in der Konfiguration.
     */
    protected function getNonPeriodicDestinationFolder(string $subFolder): ?string {
        $this->logError("Keine Konfiguration für eine periodische Ablage in den Ordner '" . $subFolder . "' gefunden.");
        return null;
    }
}

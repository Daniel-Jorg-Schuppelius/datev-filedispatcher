<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices;

use App\Helper\InternalStoreMapper;
use App\Traits\PeriodicFileServiceTrait;

abstract class PeriodicFileServiceAbstract extends FileServiceAbstract {
    use PeriodicFileServiceTrait;

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        $subFolder = $this->getSubFolder();
        $requiresPeriod = InternalStoreMapper::requiresPeriod($subFolder);
        $requiresYear = InternalStoreMapper::requiresYear($subFolder);

        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        $subFolder = $this->prepareSubFolder($subFolder, $requiresPeriod, $requiresYear);

        if ($requiresPeriod) {
            $this->logInfo("Nutze Monatsablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted . DIRECTORY_SEPARATOR . $monthFormatted);
        } elseif ($requiresYear) {
            $this->logInfo("Nutze Jahresablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted);
        }

        $this->logError("Keine Konfiguration für eine periodische Ablage in den Ordner '" . $subFolder . "' gefunden.");
        return null;
    }
}

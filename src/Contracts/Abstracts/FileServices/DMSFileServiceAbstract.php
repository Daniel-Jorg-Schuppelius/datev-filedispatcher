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
use RuntimeException;

abstract class DMSFileServiceAbstract extends FileServiceAbstract {
    use PeriodicFileServiceTrait;

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        $subFolder = $this->getSubFolder();
        $requiresPeriod = InternalStoreMapper::requiresPeriod($subFolder);
        $requiresYear = InternalStoreMapper::requiresYear($subFolder);

        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        $subFolder = $this->prepareSubFolder($subFolder, $requiresPeriod, $requiresYear);

        if (!$requiresPeriod && !$requiresYear) {
            return parent::getDestinationFolder();
        }

        $client = $this->client;
        if (is_null($client)) {
            $this->logError("Kein Client gesetzt, der Zielordner für '" . $subFolder . "' kann nicht bestimmt werden.");
            return null;
        }

        if ($requiresPeriod) {
            $this->logInfo("Nutze Monatsablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($client, $subFolder, $yearFormatted . DIRECTORY_SEPARATOR . $monthFormatted);
        }

        $this->logInfo("Nutze Jahresablage für den Ordner '" . $subFolder . "'.");
        return InternalStoreMapper::getInternalStorePath($client, $subFolder, $yearFormatted);
    }

    protected function getSubFolder(): string {
        $document = $this->document;
        if (is_null($document)) {
            $this->logError("Kein Dokument gesetzt, der Unterordner kann nicht bestimmt werden.");
            return '';
        }

        return InternalStoreMapper::getMapping4InternalStorePath($document) ?? '';
    }

    protected function setPropertiesFromDMS(string $documentNumber, bool $withPayroll = false): void {
        parent::setPropertiesFromDMS($documentNumber, $withPayroll);

        $document = $this->document;
        if (is_null($document)) {
            self::logErrorAndThrow(RuntimeException::class, "Dokument konnte im DMS nicht gefunden werden: $documentNumber");
        }

        $this->setDate($document->getYear(), $document->getMonth());
    }
}

<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DMSFileServiceAbstract.php
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

    /**
     * Ohne Jahres- oder Monatsablage gilt das DMS-Mapping des Dokuments.
     */
    protected function getNonPeriodicDestinationFolder(string $subFolder): ?string {
        return parent::getDestinationFolder();
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

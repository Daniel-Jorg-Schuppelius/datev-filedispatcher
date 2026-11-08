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

use App\Contracts\Interfaces\FileServices\FileServiceInterface;
use App\Helper\{FilenameSanitizer, InternalStoreMapper};
use CommonToolkit\Helper\FileSystem\File;
use OutOfRangeException;
use RuntimeException;

abstract class FileServiceAbstract extends FileHandlerAbstract implements FileServiceInterface {
    protected const SUBFOLDER = '';

    public function getDestinationFolder(): ?string {
        $this->validateConfig();

        $client = $this->client;
        $document = $this->document;

        if (!is_null($client) && !is_null($document)) {
            return InternalStoreMapper::getInternalStorePath4Document($client, $document);
        } elseif (!is_null($client) && !empty($this->getSubFolder())) {
            return InternalStoreMapper::getInternalStorePath($client, $this->getSubFolder());
        }

        return null;
    }

    public function process(): void {
        $this->logNotice("Verarbeite Datei: {$this->file} mit FileService: " . static::class . ".");

        $destinationFolder = $this->requireDestinationFolder();

        File::move($this->file, $destinationFolder, FilenameSanitizer::sanitize($this->getDestinationFilename()));
    }

    protected function requireDestinationFolder(): string {
        $destinationFolder = $this->getDestinationFolder();
        if (is_null($destinationFolder)) {
            self::logErrorAndThrow(RuntimeException::class, "Zielordner konnte nicht bestimmt werden für die Datei: {$this->file}");
        }

        return $destinationFolder;
    }

    protected function getDestinationFilename(): string {
        return $this->getFilename();
    }

    protected function getSubFolder(): string {
        return static::SUBFOLDER;
    }

    protected function validateConfig(): void {
        if (is_null($this->config->getInternalStorePath())) {
            self::logCriticalAndThrow(OutOfRangeException::class, "Ungültige Konfiguration für den internen Speicherpfad.");
        }
    }
}

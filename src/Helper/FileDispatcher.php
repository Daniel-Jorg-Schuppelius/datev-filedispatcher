<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileDispatcher.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper;

use App\Contracts\Abstracts\HelperAbstract;
use App\Contracts\Interfaces\FileServiceInterface;
use App\Helper\FileSystem\File;
use App\Helper\FileSystem\Files;
use App\Helper\FileSystem\FileTypes\PdfFile;
use App\Helper\FileSystem\FileTypes\TifFile;
use App\Helper\FileSystem\FileTypes\ZipFile;
use Exception;
use ReflectionClass;

class FileDispatcher extends HelperAbstract {
    protected static string $servicesDirectory = __DIR__ . '/../Services';
    protected static string $servicesNamespace = 'App\\Services';
    protected static ?array $services = null;

    protected static array $fileTypesWithoutPreProcessing = ['xlsm', 'csv'];

    protected static function setServices(): void {
        self::setLogger();

        if (is_null(self::$services)) {
            self::$services = [];
            $servicesDir = realpath(self::$servicesDirectory);

            if ($servicesDir === false) {
                self::$logger->error("Das Verzeichnis für Services konnte nicht aufgelöst werden: " . self::$servicesDirectory);
                return;
            }

            $files = Files::get($servicesDir, true, ['php']);
            foreach ($files as $file) {
                $relativePath = str_replace($servicesDir . DIRECTORY_SEPARATOR, '', $file);
                $className = self::$servicesNamespace . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', pathinfo($relativePath, PATHINFO_DIRNAME)) . '\\' . pathinfo($file, PATHINFO_FILENAME);

                if (class_exists($className)) {
                    $reflectionClass = new ReflectionClass($className);
                    if ($reflectionClass->implementsInterface(FileServiceInterface::class) && !$reflectionClass->isAbstract()) {
                        self::$services[] = $className;
                        self::$logger->info("Serviceklasse gefunden und hinzugefügt: $className");
                    }
                } else {
                    self::$logger->warning("Klasse existiert nicht oder konnte nicht geladen werden: $className");
                }
            }

            if (empty(self::$services)) {
                self::$logger->warning("Keine passenden Serviceklassen gefunden.");
            }
        }
    }

    public static function processFile($filename): void {
        self::setLogger();
        self::setServices();

        try {
            if (in_array(pathinfo($filename, PATHINFO_EXTENSION), self::$fileTypesWithoutPreProcessing) || self::preProcessFile($filename)) {
                foreach (self::$services as $serviceClass) {
                    if ($serviceClass::matchesPattern($filename)) {
                        $service = new $serviceClass($filename);
                        $service->process();
                        return;
                    }
                }

                self::$logger->warning("Kein passender Service gefunden für Datei: $filename");
            }
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Verarbeitung der Datei $filename: " . $e->getMessage());
        }
    }

    private static function preProcessFile(string $filename): bool {
        self::setLogger();

        $fileType = pathinfo($filename, PATHINFO_EXTENSION);
        $result = false;

        switch (strtolower($fileType)) {
            case 'zip':
                self::preProcessZipFile($filename);
                break;

            case 'tif':
                self::preProcessTiffFile($filename);
                break;

            case 'pdf':
                self::preProcessPdfFile($filename);
                $result = true;
                break;

            default:
                self::$logger->warning("Unbekannter Dateityp: $fileType für Datei $filename");
                break;
        }

        return $result;
    }

    private static function checkAndRepairFile(string $filename, string $expectedMimeType, callable $repairFunction): void {
        self::setLogger();

        $mimeType = File::mimeType($filename);

        if ($mimeType !== $expectedMimeType) {
            self::$logger->warning("$filename ist ungültig, versuche zu reparieren.");
            $repairFunction($filename);
        }
    }

    private static function preProcessTiffFile(string $filename): void {
        self::setLogger();

        try {
            self::checkAndRepairFile($filename, 'image/tiff', [TifFile::class, 'repair']);

            TifFile::convertToPdf($filename);
            self::$logger->info("TIFF-Datei $filename wurde erfolgreich in PDF umgewandelt.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der TIFF-Datei $filename: " . $e->getMessage());
        }
    }

    private static function preProcessPdfFile(string $filename): void {
        self::setLogger();

        try {
            $isValid = PdfFile::isValid($filename);
            if (!$isValid) {
                throw new Exception("Fehlerhafte PDF-Datei: $filename");
            }

            self::$logger->info("PDF-Datei $filename erfolgreich validiert.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der PDF-Datei $filename: " . $e->getMessage());
        }
    }

    private static function preProcessZipFile(string $filename): void {
        self::setLogger();

        try {
            $isValid = ZipFile::isValid($filename);
            if (!$isValid) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $filename");
            }

            self::$logger->info("ZIP-Archiv $filename erfolgreich validiert.");
            ZipFile::extract($filename, pathinfo($filename, PATHINFO_DIRNAME));
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung des ZIP-Archivs $filename: " . $e->getMessage());
        }
    }
}

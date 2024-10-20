<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileDispatcher.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper;

use App\Contracts\Abstracts\HelperAbstract;
use App\Contracts\Interfaces\FileServices\FileServiceInterface;
use App\Contracts\Interfaces\FileServices\PreProcessFileServiceInterface;
use App\Helper\FileSystem\File;
use App\Helper\FileSystem\Files;
use App\Helper\FileSystem\FileTypes\PdfFile;
use App\Helper\FileSystem\FileTypes\TifFile;
use App\Helper\FileSystem\FileTypes\ZipFile;
use Exception;
use ReflectionClass;

class FileDispatcher extends HelperAbstract {
    protected static string $servicesDirectory = __DIR__ . '/../Services';
    protected static string $preProcessServicesDirectory = __DIR__ . '/../PreProcessServices';
    protected static string $servicesNamespace = 'App\\Services';
    protected static string $preProcessNamespace = 'App\\PreProcessServices';
    protected static ?array $services = null;
    protected static ?array $preProcessServices = null;
    protected static array $fileTypesWithoutGenericPreProcessing = ['xlsm', 'csv'];

    // Allgemeine Methode für das Setzen von Services oder PreProcessServices
    protected static function setServiceClasses(string $directory, string $namespace, string $interface, ?array &$serviceStorage): void {
        self::setLogger();

        if (is_null($serviceStorage)) {
            $serviceStorage = [];
            $serviceDir = realpath($directory);

            if ($serviceDir === false) {
                self::$logger->error("Das Verzeichnis für Services konnte nicht aufgelöst werden: " . $directory);
                return;
            }

            $files = Files::get($serviceDir, true, ['php']);
            foreach ($files as $file) {
                $relativePath = str_replace($serviceDir . DIRECTORY_SEPARATOR, '', $file);
                $className = ($relativePath != pathinfo($file, PATHINFO_BASENAME))
                    ? $namespace . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', pathinfo($relativePath, PATHINFO_DIRNAME)) . '\\' . pathinfo($file, PATHINFO_FILENAME)
                    : $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

                if (class_exists($className)) {
                    $reflectionClass = new ReflectionClass($className);
                    if ($reflectionClass->implementsInterface($interface) && !$reflectionClass->isAbstract()) {
                        $serviceStorage[] = $className;
                        self::$logger->debug("Serviceklasse gefunden und erfolgreich hinzugefügt: $className");
                    }
                } else {
                    self::$logger->warning("Klasse existiert nicht oder konnte nicht geladen werden: $className");
                }
            }

            if (empty($serviceStorage)) {
                self::$logger->warning("Keine passenden Services gefunden.");
            }
        }
    }

    protected static function setServices(): void {
        self::setServiceClasses(self::$servicesDirectory, self::$servicesNamespace, FileServiceInterface::class, self::$services);
    }

    protected static function setPreProcessServices(): void {
        self::setServiceClasses(self::$preProcessServicesDirectory, self::$preProcessNamespace, PreProcessFileServiceInterface::class, self::$preProcessServices);
    }

    public static function processFile($filename): void {
        self::setLogger();
        self::setServices();
        self::setPreProcessServices();

        try {
            if (self::preProcessFile($filename) && File::exists($filename)) {
                foreach (self::$services as $serviceClass) {
                    if ($serviceClass::matchesPattern($filename)) {
                        self::$logger->debug("Service: " . $serviceClass . " für Datei: $filename gefunden.");
                        $service = new $serviceClass($filename);
                        $service->process();
                        return;
                    }
                }
                self::$logger->warning("Kein passender Service für Datei: $filename gefunden.");
            }
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Verarbeitung der Datei $filename: " . $e->getMessage());
            throw $e;
        }
    }

    private static function preProcessFile(string $filename): bool {
        self::setLogger();

        $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        try {
            foreach (self::$preProcessServices as $preProcessServiceClass) {
                if ($preProcessServiceClass::matchesPattern($filename)) {
                    self::$logger->debug("PreProcessService: " . $preProcessServiceClass . " für Datei: $filename gefunden.");
                    $preProcessService = new $preProcessServiceClass($filename);
                    if ($preProcessService->preProcess()) {
                        return true;
                    }
                }
            }

            if (in_array($fileType, self::$fileTypesWithoutGenericPreProcessing)) {
                self::$logger->notice("Kein passender preProcessService für Datei: $filename gefunden.");
                return true; // Datei benötigt keine generische Vorverarbeitung
            }

            self::$logger->notice("Kein passender preProcessService für Datei: $filename gefunden. Versuche generische Vorverarbeitung.");
            return self::genericPreProcessFile($filename, $fileType);
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der Datei $filename: " . $e->getMessage());
            return false;
        }
    }

    private static function genericPreProcessFile(string $filename, string $fileType): bool {
        switch ($fileType) {
            case 'zip':
                self::preProcessZipFile($filename);
                break;
            case 'tif':
                self::preProcessTiffFile($filename);
                break;
            case 'pdf':
                self::preProcessPdfFile($filename);
                break;
            default:
                self::$logger->warning("Unbekannter Dateityp: $fileType für Datei $filename");
                return false;
        }
        return true;
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
            if (!PdfFile::isValid($filename)) {
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
            if (!ZipFile::isValid($filename)) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $filename");
            }
            self::$logger->info("ZIP-Archiv $filename erfolgreich validiert.");
            ZipFile::extract($filename, pathinfo($filename, PATHINFO_DIRNAME));
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung des ZIP-Archivs $filename: " . $e->getMessage());
        }
    }
}

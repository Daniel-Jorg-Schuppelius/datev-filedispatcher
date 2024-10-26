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

use App\Config\Config;
use App\Contracts\Abstracts\HelperAbstract;
use App\Contracts\Interfaces\FileServices\FileServiceInterface;
use App\Contracts\Interfaces\FileServices\PreProcessFileServiceInterface;
use App\Helper\FileSystem\File;
use App\Helper\FileSystem\Files;
use App\Helper\FileSystem\FileTypes\CsvFile;
use App\Helper\FileSystem\FileTypes\PdfFile;
use App\Helper\FileSystem\FileTypes\TifFile;
use App\Helper\FileSystem\FileTypes\XmlFile;
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
    protected static array $fileTypesWithoutGenericPreProcessing = ['xlsm', 'txt'];

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

    public static function processFile($file): void {
        self::setLogger();
        self::setServices();
        self::setPreProcessServices();

        $config = Config::getInstance();
        $excludedFolders = $config->getExcludedFolders();

        if (empty($file)) {
            self::$logger->warning("Keine Datei zur Verarbeitung übergeben.");
            return;
        }

        if (!is_null($excludedFolders)) {
            foreach ($excludedFolders as $folder) {
                if (strpos($file, $folder) === 0) {
                    self::$logger->info("Die Datei $file liegt im ausgeschlossenen Ordner $folder und wird nicht verarbeitet.");
                    return;
                }
            }
        }

        if (!File::exists($file)) {
            self::$logger->warning("Datei $file existiert nicht.");
            return;
        }

        try {
            if (self::preProcessFile($file) && File::exists($file)) {
                foreach (self::$services as $serviceClass) {
                    if ($serviceClass::matchesPattern($file)) {
                        self::$logger->debug("Service: " . $serviceClass . " für Datei: $file gefunden.");
                        $service = new $serviceClass($file);
                        $service->process();
                        return;
                    }
                }
                self::$logger->warning("Kein passender Service für Datei: $file gefunden.");
            }
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Verarbeitung der Datei $file: " . $e->getMessage());
            throw $e;
        }
    }

    private static function preProcessFile(string $file): bool {
        self::setLogger();

        $fileType = pathinfo($file, PATHINFO_EXTENSION);

        try {
            foreach (self::$preProcessServices as $preProcessServiceClass) {
                if ($preProcessServiceClass::matchesPattern($file)) {
                    self::$logger->debug("PreProcessService: " . $preProcessServiceClass . " für Datei: $file gefunden.");
                    $preProcessService = new $preProcessServiceClass($file);
                    if ($preProcessService->preProcess()) {
                        return true;
                    }
                }
            }

            if (in_array($fileType, self::$fileTypesWithoutGenericPreProcessing)) {
                self::$logger->notice("Kein passender preProcessService für Datei: $file gefunden.");
                return true; // Datei benötigt keine generische Vorverarbeitung
            }

            self::$logger->notice("Kein passender preProcessService für Datei: $file gefunden. Versuche generische Vorverarbeitung.");
            return self::genericPreProcessFile($file, $fileType);
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der Datei $file: " . $e->getMessage());
            return false;
        }
    }

    private static function genericPreProcessFile(string $file, string $fileType): bool {
        switch (strtolower($fileType)) {
            case 'csv':
                self::preProcessCsvFile($file);
                break;
            case 'pdf':
                self::preProcessPdfFile($file);
                break;
            case 'tif':
                self::preProcessTiffFile($file);
                break;
            case 'xml':
                self::preProcessXmlFile($file);
                break;
            case 'zip':
                self::preProcessZipFile($file);
                break;
            default:
                self::$logger->warning("Unbekannter Dateityp: $fileType für Datei $file");
                return false;
        }
        return true;
    }

    private static function checkAndRepairFile(string $file, string $expectedMimeType, callable $repairFunction): void {
        self::setLogger();
        $mimeType = File::mimeType($file);

        if ($mimeType !== $expectedMimeType) {
            self::$logger->warning("$file ist ungültig, versuche zu reparieren.");
            $repairFunction($file);
        }
    }

    private static function preProcessCsvFile(string $file): void {
        self::setLogger();
        try {
            if (!CsvFile::isWellFormed($file)) {
                throw new Exception("Fehlerhafte CSV-Datei: $file");
            }
            self::$logger->info("CSV-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der CSV-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessPdfFile(string $file): void {
        self::setLogger();
        try {
            if (!PdfFile::isValid($file)) {
                throw new Exception("Fehlerhafte PDF-Datei: $file");
            }
            self::$logger->info("PDF-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der PDF-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessTiffFile(string $file): void {
        self::setLogger();
        try {
            self::checkAndRepairFile($file, 'image/tiff', [TifFile::class, 'repair']);
            TifFile::convertToPdf($file);
            self::$logger->info("TIFF-Datei $file wurde erfolgreich in PDF umgewandelt.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der TIFF-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessXmlFile(string $file): void {
        self::setLogger();
        try {
            if (!XmlFile::isWellFormed($file)) {
                throw new Exception("Fehlerhafte XML-Datei: $file");
            }
            self::$logger->info("XML-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung der XML-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessZipFile(string $file): void {
        self::setLogger();
        try {
            if (!ZipFile::isValid($file)) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $file");
            }
            self::$logger->info("ZIP-Archiv $file erfolgreich validiert.");
            ZipFile::extract($file, pathinfo($file, PATHINFO_DIRNAME));
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Vorverarbeitung des ZIP-Archivs $file: " . $e->getMessage());
        }
    }
}

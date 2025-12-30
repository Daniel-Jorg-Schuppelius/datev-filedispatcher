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
use App\Contracts\Interfaces\FileServices\FileServiceInterface;
use App\Contracts\Interfaces\FileServices\PreProcessFileServiceInterface;
use App\Factories\LoggerFactory;
use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\FileTypes\CsvFile;
use CommonToolkit\Helper\FileSystem\FileTypes\PdfFile;
use CommonToolkit\Helper\FileSystem\FileTypes\TifFile;
use CommonToolkit\Helper\FileSystem\FileTypes\XmlFile;
use CommonToolkit\Helper\FileSystem\FileTypes\ZipFile;
use ConfigToolkit\ClassLoader;
use ERRORToolkit\LoggerRegistry;
use Exception;

class FileDispatcher extends HelperAbstract {
    protected static string $servicesDirectory = __DIR__ . '/../Services';
    protected static string $preProcessServicesDirectory = __DIR__ . '/../PreProcessServices';
    protected static string $servicesNamespace = 'App\\Services';
    protected static string $preProcessNamespace = 'App\\PreProcessServices';
    protected static ?array $services = null;
    protected static ?array $preProcessServices = null;
    protected static array $fileTypesWithoutGenericPreProcessing = ['xlsm', 'txt'];

    /**
     * Prüft ob die Datei eine temporäre oder ungültige Datei ist
     * - 0-Byte Dateien
     * - Windows 8.3 Short Names (z.B. NDH6SA~M)
     * - Dateien ohne Erweiterung
     * - Versteckte Dateien (beginnend mit .)
     */
    protected static function isTemporaryOrInvalidFile(string $file): bool {
        $basename = pathinfo($file, PATHINFO_BASENAME);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        // 0-Byte Dateien ignorieren
        if (File::exists($file) && filesize($file) === 0) {
            self::logInfo("Überspringe 0-Byte Datei: $file");
            return true;
        }

        // Dateien ohne Erweiterung ignorieren (außer bekannte Ausnahmen)
        if (empty($extension)) {
            self::logInfo("Überspringe Datei ohne Erweiterung: $file");
            return true;
        }

        // Versteckte Dateien ignorieren
        if (strpos($basename, '.') === 0) {
            self::logInfo("Überspringe versteckte Datei: $file");
            return true;
        }

        return false;
    }

    // Allgemeine Methode für das Setzen von Services oder PreProcessServices
    protected static function setServiceClasses(string $directory, string $namespace, string $interface, ?array &$serviceStorage): void {
        if (is_null($serviceStorage)) {
            $classLoader = new ClassLoader($directory, $namespace, $interface, self::$logger);
            $serviceStorage = $classLoader->getClasses();
        }
    }

    protected static function setServices(): void {
        self::setServiceClasses(self::$servicesDirectory, self::$servicesNamespace, FileServiceInterface::class, self::$services);
    }

    protected static function setPreProcessServices(): void {
        self::setServiceClasses(self::$preProcessServicesDirectory, self::$preProcessNamespace, PreProcessFileServiceInterface::class, self::$preProcessServices);
    }

    public static function processFile($file): void {
        LoggerRegistry::setLogger(LoggerFactory::getLogger());

        self::setServices();
        self::setPreProcessServices();

        $excludedFolders = Config::getInstance()->getExcludedFolders();

        if (empty($file)) {
            self::logWarning("Keine Datei zur Verarbeitung übergeben.");
            return;
        }

        if (!is_null($excludedFolders)) {
            foreach ($excludedFolders as $folder) {
                if (strpos($file, $folder) === 0) {
                    self::logInfo("Die Datei $file liegt im ausgeschlossenen Ordner $folder und wird nicht verarbeitet.");
                    return;
                }
            }
        }

        if (!File::exists($file)) {
            self::logWarning("Datei $file existiert nicht.");
            return;
        }

        // Prüfe auf temporäre oder ungültige Dateien
        if (self::isTemporaryOrInvalidFile($file)) {
            return;
        }

        try {
            if (self::preProcessFile($file) && File::exists($file)) {
                foreach (self::$services as $serviceClass) {
                    if ($serviceClass::matchesPattern($file)) {
                        self::logDebug("Service: " . $serviceClass . " für Datei: $file gefunden.");
                        $service = new $serviceClass($file);
                        $service->process();
                        return;
                    }
                }
                self::logWarning("Kein passender Service für Datei: $file gefunden.");
            }
        } catch (Exception $e) {
            self::logError("Fehler bei der Verarbeitung der Datei $file: " . $e->getMessage());
            throw $e;
        }
    }

    private static function preProcessFile(string $file): bool {
        $fileType = pathinfo($file, PATHINFO_EXTENSION);

        try {
            foreach (self::$preProcessServices as $preProcessServiceClass) {
                if ($preProcessServiceClass::matchesPattern($file)) {
                    self::logDebug("PreProcessService: " . $preProcessServiceClass . " für Datei: $file gefunden.");
                    $preProcessService = new $preProcessServiceClass($file);
                    if ($preProcessService->preProcess()) {
                        return true;
                    }
                }
            }

            if (in_array($fileType, self::$fileTypesWithoutGenericPreProcessing)) {
                self::logNotice("Kein passender preProcessService für Datei: $file gefunden.");
                return true; // Datei benötigt keine generische Vorverarbeitung
            }

            self::logNotice("Kein passender preProcessService für Datei: $file gefunden. Versuche generische Vorverarbeitung.");
            return self::genericPreProcessFile($file, $fileType);
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung der Datei $file: " . $e->getMessage());
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
                self::logWarning("Unbekannter Dateityp: $fileType für Datei $file");
                return false;
        }
        return true;
    }

    private static function checkAndRepairFile(string $file, string $expectedMimeType, callable $repairFunction): void {
        $mimeType = File::mimeType($file);

        if ($mimeType !== $expectedMimeType) {
            self::logWarning("$file ist ungültig, versuche zu reparieren.");
            $repairFunction($file);
        }
    }

    private static function preProcessCsvFile(string $file): void {
        try {
            if (!CsvFile::isWellFormed($file)) {
                throw new Exception("Fehlerhafte CSV-Datei: $file");
            }
            self::logInfo("CSV-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung der CSV-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessPdfFile(string $file): void {
        try {
            if (!PdfFile::isValid($file)) {
                throw new Exception("Fehlerhafte PDF-Datei: $file");
            }
            self::logInfo("PDF-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung der PDF-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessTiffFile(string $file): void {
        try {
            self::checkAndRepairFile($file, 'image/tiff', [TifFile::class, 'repair']);
            TifFile::convertToPdf($file);
            self::logInfo("TIFF-Datei $file wurde erfolgreich in PDF umgewandelt.");
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung der TIFF-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessXmlFile(string $file): void {
        try {
            if (!XmlFile::isWellFormed($file)) {
                throw new Exception("Fehlerhafte XML-Datei: $file");
            }
            self::logInfo("XML-Datei $file erfolgreich validiert.");
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung der XML-Datei $file: " . $e->getMessage());
        }
    }

    private static function preProcessZipFile(string $file): void {
        try {
            if (!ZipFile::isValid($file)) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $file");
            }
            self::logInfo("ZIP-Archiv $file erfolgreich validiert.");
            ZipFile::extract($file, pathinfo($file, PATHINFO_DIRNAME));
        } catch (Exception $e) {
            self::logError("Fehler bei der Vorverarbeitung des ZIP-Archivs $file: " . $e->getMessage());
        }
    }
}

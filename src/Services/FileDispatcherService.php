<?php

namespace App\Services;

use APIToolkit\Logger\ConsoleLoggerFactory;
use App\Helper\File;
use App\Helper\FileTypes\PdfFile;
use App\Helper\FileTypes\TifFile;
use App\Helper\FileTypes\ZipFile;
use App\Services\Payroll\PayrollFileService;
use Exception;
use Psr\Log\LoggerInterface;

class FileDispatcherService {
    protected static ?LoggerInterface $logger = null;

    protected static array $filePatterns = [
        '/^(\d+)_\d+_[A-Za-z_]+_(\d{2})_(\d{4})_.+\.pdf$/' => PayrollFileService::class,
        // '/SEPA-\d{5}-\d{4}_\d{2}-.+\.xml$/' => SepaFileService::class,
        // '/DTVF_\d+_\d+_LOHNBUCHUNGEN_LUG_\d{6}_\d{8}_\d+\.csv$/' => BookingBatchService::class,
        // Weitere Schemata und zugehörige Services hier hinzufügen
    ];

    public static function getServiceForFile(string $filename): mixed {
        if (self::$logger === null) {
            self::$logger = ConsoleLoggerFactory::getLogger();
        }

        foreach (self::$filePatterns as $pattern => $serviceClass) {
            if (preg_match($pattern, $filename)) {
                return new $serviceClass($filename);
            }
        }

        return null;
    }

    public static function processFile($filename): void {
        try {
            $fileType = pathinfo($filename, PATHINFO_EXTENSION);

            switch (strtolower($fileType)) {
                case 'zip':
                    self::$logger->info("Extrahiere ZIP-Datei: $filename");
                    self::processZipFile($filename);
                    break;

                case 'tif':
                    self::$logger->info("Verarbeite TIFF-Datei: $filename");
                    self::processTiffFile($filename);
                    break;

                case 'pdf':
                    self::$logger->info("Verarbeite PDF-Datei: $filename");
                    self::processPdfFile($filename);
                    break;

                default:
                    self::$logger->warning("Unbekannter Dateityp: $fileType für Datei $filename");
                    break;
            }
        } catch (Exception $e) {
            self::$logger->error("Fehler bei der Verarbeitung der Datei $filename: " . $e->getMessage());
        }
    }

    private static function checkAndRepairFile(string $filename, string $expectedMimeType, callable $repairFunction): void {
        $mimeType = File::mimeType($filename);

        if ($mimeType !== $expectedMimeType) {
            self::$logger->warning("$filename ist ungültig, versuche zu reparieren.");
            $repairFunction($filename);
        }
    }

    private static function processTiffFile(string $filename): void {
        try {
            self::checkAndRepairFile($filename, 'image/tiff', [TifFile::class, 'repair']);

            TifFile::convertToPdf($filename);
            self::$logger->info("TIFF-Datei $filename wurde erfolgreich in PDF umgewandelt.");
        } catch (Exception $e) {
            self::$logger->error("Fehler beim Verarbeiten der TIFF-Datei $filename: " . $e->getMessage());
        }
    }

    private static function processPdfFile(string $filename): void {
        try {
            $isValid = PdfFile::isValid($filename);
            if (!$isValid) {
                throw new Exception("Fehlerhafte PDF-Datei: $filename");
            }

            self::$logger->info("PDF-Datei $filename erfolgreich validiert.");
        } catch (Exception $e) {
            self::$logger->error("Fehler beim Verarbeiten der PDF-Datei $filename: " . $e->getMessage());
        }
    }

    private static function processZipFile(string $filename): void {
        try {
            $isValid = ZipFile::isValid($filename);
            if (!$isValid) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $filename");
            }

            self::$logger->info("ZIP-Archiv $filename erfolgreich validiert.");
            ZipFile::extract($filename, pathinfo($filename, PATHINFO_DIRNAME));
        } catch (Exception $e) {
            self::$logger->error("Fehler beim Verarbeiten des ZIP-Archivs $filename: " . $e->getMessage());
        }
    }
}

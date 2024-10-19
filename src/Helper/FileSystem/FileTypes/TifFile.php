<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TifFileHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper\FileSystem\FileTypes;

use APIToolkit\Exceptions\NotFoundException;
use App\Contracts\Abstracts\HelperAbstract;
use App\Helper\FileSystem\File;
use App\Helper\FileSystem\Files;
use App\Helper\Shell;
use Exception;

class TifFile extends HelperAbstract {
    private const FILE_EXTENSION_PATTERN = "/\.tif{1,2}$/i";

    public static function repair(string $filename, bool $forceRepair = false): string {

        self::setLogger();
        $mimeType = File::mimeType($filename);

        if ($mimeType !== 'image/tiff' && preg_match(self::FILE_EXTENSION_PATTERN, $filename)) {
            $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".jpg", $filename);
            File::rename($filename, $newFilename);

            $command = sprintf("convert %s %s", escapeshellarg($newFilename), escapeshellarg($filename));
            if (Shell::executeShellCommand($command)) {
                self::$logger->info("TIFF-Datei erfolgreich von JPEG repariert: $newFilename");
            } else {
                self::$logger->error("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
                throw new Exception("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
            }

            File::delete($newFilename);

            return $filename;
        } elseif ($mimeType === 'image/tiff' && !preg_match(self::FILE_EXTENSION_PATTERN, $filename)) {
            $newFilename = preg_replace("/\.[^.]+$/", ".tif", $filename);
            File::rename($filename, $newFilename);
            return self::repair($newFilename);
        } elseif ($mimeType === 'image/tiff' && preg_match(self::FILE_EXTENSION_PATTERN, $filename)) {
            self::$logger->info("Die Datei ist bereits im TIFF-Format: $filename");
            if ($forceRepair) {
                $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".original.tif", $filename);
                File::rename($filename, $newFilename);

                $command = sprintf("convert %s -monochrome %s", escapeshellarg($newFilename), escapeshellarg($filename));
                if (Shell::executeShellCommand($command)) {
                    self::$logger->info("TIFF-Datei erfolgreich repariert: $newFilename");
                } else {
                    self::$logger->error("Fehler bei der Reparatur von TIFF: $newFilename");
                    throw new Exception("Fehler bei der Reparatur von TIFF: $newFilename");
                }

                File::delete($newFilename);

                return $filename;
            }
        } else {
            self::$logger->error("Die Datei ist nicht im TIFF-Format: $filename");
            throw new Exception("Die Datei ist nicht im TIFF-Format: $filename");
        }

        return $filename;
    }

    public static function convertToPdf(string $tiffFile, ?string $pdfFile = null, bool $compressed = true, bool $deleteSourceFile = true): void {
        self::setLogger();
        if (!File::exists($tiffFile)) {
            self::$logger->error("Die Datei existiert nicht: $tiffFile");
            throw new NotFoundException("Die Datei existiert nicht: $tiffFile");
        } elseif (!is_null($pdfFile) && File::exists($pdfFile)) {
            self::$logger->error("Die Datei existiert bereits: $pdfFile");
            throw new Exception("Die Datei existiert bereits: $pdfFile");
        } elseif (!self::isValid($tiffFile)) {
            try {
                $tiffFile = self::repair($tiffFile);  // Reparierter Dateiname wird zurückgegeben
            } catch (Exception $e) {
                self::$logger->error("Die Datei ist nicht gültig: $tiffFile");
                throw new Exception("Die Datei ist nicht gültig: $tiffFile");
            }
        }

        if (is_null($pdfFile)) {
            $pdfFile = preg_replace(self::FILE_EXTENSION_PATTERN, ".pdf", $tiffFile);
        }

        if (File::exists($pdfFile)) {
            self::$logger->error("Die Datei existiert bereits: $pdfFile");
            throw new Exception("Die Datei existiert bereits: $pdfFile");
        }

        $command = $compressed
            ? "tiff2pdf -F -j -c internal_dispatcher -a internal_dispatcher -m0 -o '$pdfFile' '$tiffFile' 2>&1"
            : "tiff2pdf -F -n -c internal_dispatcher -a internal_dispatcher -m0 -o '$pdfFile' '$tiffFile' 2>&1";

        Shell::executeShellCommand($command);

        if (PdfFile::isValid($pdfFile)) {
            self::$logger->info("TIFF-Datei erfolgreich in PDF umgewandelt: $tiffFile");
        } elseif ($compressed) {
            self::$logger->warning("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile. Versuche erneute Konvertierung ohne Kompression.");
            File::delete($pdfFile);
            TifFile::repair($tiffFile, true);
            self::convertToPdf($tiffFile, $pdfFile, false, false);

            if (PdfFile::isValid($pdfFile)) {
                self::$logger->info("TIFF-Datei erfolgreich ohne Kompression in PDF umgewandelt: $tiffFile");
            } else {
                self::$logger->error("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
                File::delete($pdfFile);
                throw new Exception("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            }
        } else {
            self::$logger->error("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            File::delete($pdfFile);
            throw new Exception("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
        }

        if ($deleteSourceFile) {
            File::delete($tiffFile);
        }
    }

    public static function merge(array $tiffFiles, string $mergedFile, bool $deleteSourceFiles = true): void {
        self::setLogger();
        $command = sprintf("tiffcp %s %s", implode(" ", array_map('escapeshellarg', $tiffFiles)), escapeshellarg($mergedFile));
        Shell::executeShellCommand($command);

        self::$logger->info("TIFF-Dateien erfolgreich zusammengeführt: $mergedFile");

        if ($deleteSourceFiles) {
            Files::delete($tiffFiles);
        }
    }

    public static function isValid(string $filename): bool {
        self::setLogger();
        if (preg_match(self::FILE_EXTENSION_PATTERN, $filename)) {
            $command = sprintf("tiffinfo %s 2>&1", escapeshellarg($filename));
            $output = [];

            if (Shell::executeShellCommand($command, $output)) {
                self::$logger->info("TIFF-Datei ist gültig: $filename");
                return true;
            } else {
                self::$logger->warning("TIFF-Datei ist ungültig: $filename");
                return false;
            }
        }
        self::$logger->warning("Datei ist keine TIFF-Datei: $filename");
        return false;
    }
}

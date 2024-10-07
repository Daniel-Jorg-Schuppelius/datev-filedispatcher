<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TifFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper\FileTypes;

use APIToolkit\Exceptions\NotFoundException;
use App\Contracts\Abstracts\HelperAbstract;
use App\Helper\File;
use App\Helper\Files;
use App\Helper\Shell;
use Exception;

class TifFile extends HelperAbstract {
    public static function repair(string $filename): string {
        self::setLogger();
        $mimeType = File::mimeType($filename);

        if ($mimeType !== 'image/tiff' && preg_match("/\.tif$/i", $filename)) {
            $newFilename = preg_replace("/\.tif$/i", ".jpg", $filename);
            File::rename($filename, $newFilename);

            $command = sprintf("convert %s %s", escapeshellarg($newFilename), escapeshellarg($filename));
            if (Shell::executeShellCommand($command)) {
                self::$logger->info("TIFF-Datei erfolgreich von JPEG repariert: $newFilename");
            } else {
                self::$logger->error("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
                throw new Exception("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
            }

            File::delete($newFilename); // Entferne die temporäre JPEG-Datei

            return $filename;
        } elseif ($mimeType === 'image/tiff' && !preg_match("/\.tif$/i", $filename)) {
            $newFilename = str_replace("." . pathinfo($filename, PATHINFO_EXTENSION), ".tif", $filename);
            File::rename($filename, $newFilename);
            return self::repair($newFilename);  // Rufe die Reparatur erneut auf mit dem neuen Dateinamen
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
        } elseif (File::exists($pdfFile)) {
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
            $pdfFile = preg_replace("/\.tif$/i", ".pdf", $tiffFile);
        }

        if (File::exists($pdfFile)) {
            self::$logger->error("Die Datei existiert bereits: $pdfFile");
            throw new Exception("Die Datei existiert bereits: $pdfFile");
        }

        $command = $compressed
            ? "tiff2pdf -F -j -c internal_dispatcher -a internal_dispatcher -m0 -o '$pdfFile' '$tiffFile'"
            : "tiff2pdf -n -F -c internal_dispatcher -a internal_dispatcher -m0 -o '$pdfFile' '$tiffFile'";

        Shell::executeShellCommand($command);

        self::$logger->info("TIFF-Datei erfolgreich in PDF umgewandelt: $tiffFile");

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
        if (preg_match("/\.tif$/i", $filename)) {
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
        return false;
    }
}

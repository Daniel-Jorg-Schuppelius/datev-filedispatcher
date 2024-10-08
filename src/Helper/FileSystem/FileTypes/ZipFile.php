<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ZipFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper\FileSystem\FileTypes;

use App\Contracts\Abstracts\HelperAbstract;
use App\Helper\FileSystem\File;
use Exception;
use ZipArchive;

class ZipFile extends HelperAbstract {
    public static function create(array $files, string $destination): bool {
        self::setLogger();
        $zip = new ZipArchive();

        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            self::$logger->error("Fehler beim Erstellen des ZIP-Archivs: $destination");
            throw new Exception("Fehler beim Erstellen des ZIP-Archivs: $destination");
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                self::$logger->error("Datei nicht gefunden: $file");
                continue;
            }

            $zip->addFile($file, basename($file));
        }

        $zip->close();
        self::$logger->info("ZIP-Archiv erfolgreich erstellt: $destination");

        return true;
    }

    public static function extract(string $filename, string $destinationFolder, bool $deleteSourceFile = true): void {
        self::setLogger();
        $zip = new ZipArchive();

        if ($zip->open($filename) === true) {
            $zip->extractTo($destinationFolder);
            $zip->close();
            self::$logger->info("ZIP-Datei erfolgreich extrahiert: $filename nach $destinationFolder");

            if ($deleteSourceFile) {
                File::delete($filename);
            }
        } else {
            self::$logger->error("Fehler beim Extrahieren der ZIP-Datei: $filename");
            throw new Exception("Fehler beim Extrahieren der ZIP-Datei: $filename");
        }
    }

    public static function isValid(string $filename): bool {
        self::setLogger();
        $zip = new ZipArchive();
        $result = $zip->open($filename);

        if ($result === true) {
            self::$logger->info("ZIP-Datei ist gültig: $filename");
            $zip->close();
            return true;
        } else {
            switch ($result) {
                case ZipArchive::ER_NOZIP:
                    self::$logger->error("Die Datei ist keine gültige ZIP-Datei: $filename");
                    break;
                case ZipArchive::ER_INCONS:
                    self::$logger->error("Das ZIP-Archiv ist inkonsistent: $filename");
                    break;
                case ZipArchive::ER_MEMORY:
                    self::$logger->error("Speicherproblem beim Öffnen des ZIP-Archivs: $filename");
                    break;
                default:
                    self::$logger->error("Unbekannter Fehler beim Öffnen des ZIP-Archivs: $filename");
            }
            return false;
        }
    }
}

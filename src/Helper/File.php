<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : File.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper;

use App\Contracts\Abstracts\HelperAbstract;
use App\Contracts\Interfaces\FileSystemInterface;
use Exception;
use finfo;

class File extends HelperAbstract implements FileSystemInterface {
    public static function mimeType(string $filename): string {
        self::setLogger();

        $result = false;
        if (function_exists('mime_content_type')) {
            self::$logger->info("Nutze mime_content_type für Erkennung des mime-types: $filename");
            $result = @mime_content_type($filename);
        } elseif (function_exists('finfo_open')) {
            self::$logger->info("Nutze finfo für Erkennung des mime-types: $filename");
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $result = $finfo->buffer(file_get_contents($filename));
        }

        if (false === $result && PHP_OS_FAMILY === 'Linux') {
            self::$logger->warning("Nutze Shell für Erkennung des mime-types: $filename");
            $result = self::mimeTypeByShell($filename);
        }
        return $result;
    }

    private static function mimeTypeByShell(string $filename): string {
        self::setLogger();

        $command = sprintf('file -b --mime-type -m /usr/share/misc/magic %s', escapeshellarg($filename));

        $output = [];
        $success = Shell::executeShellCommand($command, $output);

        if (!$success || empty($output)) {
            self::$logger->error("Problem bei der Bestimmung des MIME-Typs für $filename");
            throw new Exception("Problem bei der Bestimmung des MIME-Typs für $filename");
        }

        return trim(implode("\n", $output));
    }

    public static function exists(string $file): bool {
        return file_exists($file);
    }

    public static function create(string $file, int $permissions = 0644, string $content = ''): void {
        self::setLogger();

        if (self::exists($file)) {
            self::$logger->error("Die Datei $file existiert bereits");
            throw new Exception("Die Datei $file existiert bereits");
        }

        if (file_put_contents($file, $content) === false) {
            self::$logger->error("Fehler beim Erstellen der Datei $file");
            throw new Exception("Fehler beim Erstellen der Datei $file");
        }

        if (!chmod($file, $permissions)) {
            self::$logger->error("Fehler beim Setzen der Berechtigungen $permissions für die Datei $file");
            throw new Exception("Fehler beim Setzen der Berechtigungen für die Datei $file");
        }

        self::$logger->info("Datei erstellt: $file mit Berechtigungen $permissions");
    }

    public static function rename(string $oldName, string $newName): void {
        self::setLogger();
        if (!self::exists($oldName)) {
            self::$logger->error("Die Datei $oldName existiert nicht");
            throw new Exception("Die Datei $oldName existiert nicht");
        }

        if (!rename($oldName, $newName)) {
            self::$logger->error("Fehler beim Umbenennen der Datei von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen der Datei von $oldName nach $newName");
        }

        self::$logger->info("Datei umbenannt von $oldName zu $newName");
    }

    public static function move(string $sourceFile, string $destinationFile): void {
        self::setLogger();
        if (!self::exists($sourceFile)) {
            self::$logger->error("Die Datei $sourceFile existiert nicht");
            throw new Exception("Die Datei $sourceFile existiert nicht");
        }

        if (!rename($sourceFile, $destinationFile)) {
            self::$logger->error("Fehler beim Verschieben der Datei von $sourceFile nach $destinationFile");
            throw new Exception("Fehler beim Verschieben der Datei von $sourceFile nach $destinationFile");
        }

        self::$logger->info("Datei von $sourceFile zu $destinationFile verschoben");
    }

    public static function delete(string $file): void {
        self::setLogger();
        if (!unlink($file)) {
            self::$logger->error("Fehler beim Löschen der Datei $file");
            throw new Exception("Fehler beim Löschen der Datei $file");
        }

        self::$logger->info("Datei gelöscht: $file");
    }

    public static function size(string $file): int {
        if (!self::exists($file)) {
            throw new Exception("Die Datei $file existiert nicht");
        }
        return filesize($file);
    }

    public static function read(string $file): string {
        self::setLogger();
        if (!self::exists($file)) {
            self::$logger->error("Die Datei $file existiert nicht");
            throw new Exception("Die Datei $file existiert nicht");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            self::$logger->error("Fehler beim Lesen der Datei $file");
            throw new Exception("Fehler beim Lesen der Datei $file");
        }

        self::$logger->info("Datei erfolgreich gelesen: $file");
        return $content;
    }

    public static function write(string $file, string $data): void {
        self::setLogger();
        if (file_put_contents($file, $data) === false) {
            self::$logger->error("Fehler beim Schreiben in die Datei $file");
            throw new Exception("Fehler beim Schreiben in die Datei $file");
        }

        self::$logger->info("Daten in Datei gespeichert: $file");
    }
}

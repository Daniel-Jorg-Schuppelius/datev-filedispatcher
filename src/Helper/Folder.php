<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Folder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper;

use App\Contracts\Abstracts\HelperAbstract;
use App\Contracts\Interfaces\FileSystemInterface;
use Exception;

class Folder extends HelperAbstract implements FileSystemInterface {
    public static function exists(string $directory): bool {
        self::setLogger();
        $exists = is_dir($directory);
        self::$logger->info("Überprüfung ob Verzeichnis existiert: $directory - " . ($exists ? 'Ja' : 'Nein'));
        return $exists;
    }

    public static function create(string $directory, int $permissions = 0755): void {
        self::setLogger();
        if (!self::exists($directory)) {
            if (!mkdir($directory, $permissions, true)) {
                self::$logger->error("Fehler beim Erstellen des Verzeichnisses: $directory");
                throw new Exception("Fehler beim Erstellen des Verzeichnisses $directory");
            }
            self::$logger->info("Verzeichnis erstellt: $directory mit Berechtigungen $permissions");
        } else {
            self::$logger->info("Verzeichnis existiert bereits: $directory");
        }
    }

    public static function rename(string $oldName, string $newName): void {
        self::setLogger();
        if (!self::exists($oldName)) {
            self::$logger->error("Das Verzeichnis $oldName existiert nicht");
            throw new Exception("Das Verzeichnis $oldName existiert nicht");
        }

        if (!rename($oldName, $newName)) {
            self::$logger->error("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
        }

        self::$logger->info("Verzeichnis umbenannt von $oldName zu $newName");
    }

    public static function delete(string $directory): void {
        self::setLogger();
        if (!self::exists($directory)) {
            self::$logger->error("Das Verzeichnis $directory existiert nicht");
            throw new Exception("Das Verzeichnis $directory existiert nicht");
        }

        if (!rmdir($directory)) {
            self::$logger->error("Fehler beim Löschen des Verzeichnisses $directory");
            throw new Exception("Fehler beim Löschen des Verzeichnisses $directory");
        }

        self::$logger->info("Verzeichnis gelöscht: $directory");
    }

    public static function move(string $sourceDirectory, string $destinationDirectory): void {
        self::setLogger();
        if (!self::exists($sourceDirectory)) {
            self::$logger->error("Das Verzeichnis $sourceDirectory existiert nicht");
            throw new Exception("Das Verzeichnis $sourceDirectory existiert nicht");
        }

        if (!rename($sourceDirectory, $destinationDirectory)) {
            self::$logger->error("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
            throw new Exception("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
        }

        self::$logger->info("Verzeichnis verschoben von $sourceDirectory nach $destinationDirectory");
    }
}

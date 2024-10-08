<?php

namespace App\Helper\FileSystem;

use App\Contracts\Abstracts\HelperAbstract;
use Exception;

class Files extends HelperAbstract {

    public static function exists(array $files): bool {
        foreach ($files as $file) {
            if (!File::exists($file)) {
                return false;
            }
        }
        return true;
    }

    public static function rename(array $filePairs): void {
        foreach ($filePairs as $oldName => $newName) {
            File::rename($oldName, $newName);
        }
    }

    public static function delete(array $files): void {
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    public static function read(array $files): array {
        $fileContents = [];
        foreach ($files as $file) {
            $fileContents[$file] = File::read($file);
        }
        return $fileContents;
    }

    public static function write(array $fileData): void {
        foreach ($fileData as $file => $data) {
            File::write($file, $data);
        }
    }

    public static function get(string $directory, bool $recursive = false, array $fileTypes = []): array {
        self::setLogger();
        $result = [];
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if ($recursive && is_dir($path)) {
                $result = array_merge($result, self::get($path, true, $fileTypes));
            } elseif (is_file($path)) {
                if (empty($fileTypes) || in_array(pathinfo($path, PATHINFO_EXTENSION), $fileTypes)) {
                    $result[] = $path;
                }
            }
        }

        if (empty($result)) {
            self::$logger->warning("Keine Dateien gefunden im Verzeichnis: $directory");
        } else {
            self::$logger->info("Dateien erfolgreich gelesen aus Verzeichnis: $directory");
        }

        return $result;
    }
}

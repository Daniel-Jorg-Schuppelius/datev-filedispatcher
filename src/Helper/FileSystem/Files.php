<?php
/*
 * Created on   : Mon Oct 14 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Files.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper\FileSystem;

use App\Contracts\Abstracts\HelperAbstract;

class Files extends HelperAbstract {

    public static function exists(array $files): bool {
        foreach ($files as $file) {
            if (!File::exists($file)) {
                return false;
            }
        }
        return true;
    }

    public static function copy(array $filePairs): void {
        foreach ($filePairs as $sourceFile => $destinationFile) {
            File::copy($sourceFile, $destinationFile);
        }
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

    public static function get(string $directory, bool $recursive = false, array $fileTypes = [], ?string $regexPattern = null): array {
        self::setLogger();
        $result = [];
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if ($recursive && is_dir($path)) {
                $result = array_merge($result, self::get($path, true, $fileTypes, $regexPattern));
            } elseif (is_file($path)) {
                if (empty($fileTypes) || in_array(pathinfo($path, PATHINFO_EXTENSION), $fileTypes)) {
                    if (is_null($regexPattern) || preg_match($regexPattern, $file)) {
                        $result[] = $path;
                    }
                }
            }
        }

        if (empty($result)) {
            self::$logger->info("Keine passenden Dateien gefunden im Verzeichnis: $directory");
        } else {
            self::$logger->debug("Dateien erfolgreich gefunden im Verzeichnis: $directory");
        }

        return $result;
    }
}

<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Files.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper;

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
}

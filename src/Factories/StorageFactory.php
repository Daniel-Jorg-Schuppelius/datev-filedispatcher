<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StorageFactory.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Factories;

use App\Config\Config;

class StorageFactory {
    private static ?string $internalStorePath = null;

    public static function getInternalStorePath(): string {
        if (self::$internalStorePath === null) {
            $config = Config::getInstance();
            self::$internalStorePath = $config->internalStorePath;
        }
        return self::$internalStorePath;
    }
}

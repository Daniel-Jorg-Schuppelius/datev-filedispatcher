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
use Datev\Entities\ClientMasterData\Clients\Client;
use InvalidArgumentException;
use RuntimeException;

class StorageFactory {
    private static ?string $internalStorePath = null;

    public static function getInternalStorePath(): string {
        if (self::$internalStorePath === null) {
            $config = Config::getInstance();
            self::$internalStorePath = $config->getInternalStorePath();

            if (!self::isInternalStorePathValid()) {
                throw new InvalidArgumentException('The InternalStorePath must contain the placeholder {tenant}. Please check your configuration');
            }
        }
        return self::$internalStorePath;
    }

    public static function getInternalStorePathForClient(Client $client): string {
        $path = realpath(str_replace("{tenant}", (string) $client->getNumber(), self::getInternalStorePath()));

        if ($path === false) {
            throw new RuntimeException('The InternalStorePath for the client could not be resolved');
        }
        return ltrim($path, DIRECTORY_SEPARATOR);
    }

    public static function setInternalStorePath(string $path): void {
        self::$internalStorePath = $path;

        if (!self::isInternalStorePathValid()) {
            throw new InvalidArgumentException('The path must contain the placeholder {tenant}');
        }
    }

    public static function isInternalStorePathValid(): bool {
        return str_contains(self::$internalStorePath, '{tenant}');
    }
}

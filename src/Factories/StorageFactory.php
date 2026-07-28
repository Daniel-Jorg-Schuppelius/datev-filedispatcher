<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StorageFactory.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Factories;

use App\Config\Config;
use Datev\Entities\ClientMasterData\Clients\Client;
use InvalidArgumentException;
use RuntimeException;

class StorageFactory {
    private static ?string $internalStorePath = null;

    public static function getInternalStorePath(): string {
        if (self::$internalStorePath === null) {
            $configuredPath = Config::getInstance()->getInternalStorePath();

            if ($configuredPath === null) {
                throw new InvalidArgumentException('The InternalStorePath is not configured. Please check your configuration');
            } elseif (!self::containsTenantPlaceholder($configuredPath)) {
                throw new InvalidArgumentException('The InternalStorePath must contain the placeholder {tenant}. Please check your configuration');
            }

            self::$internalStorePath = $configuredPath;
        }
        return self::$internalStorePath;
    }

    public static function getInternalStorePathForClient(Client $client): string {
        $path = realpath(str_replace("{tenant}", (string) $client->getNumber(), self::getInternalStorePath()));

        if ($path === false) {
            throw new RuntimeException('The InternalStorePath for the client could not be resolved');
        }
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function setInternalStorePath(string $path): void {
        if (!self::containsTenantPlaceholder($path)) {
            throw new InvalidArgumentException('The path must contain the placeholder {tenant}');
        }

        self::$internalStorePath = $path;
    }

    public static function isInternalStorePathValid(): bool {
        return !is_null(self::$internalStorePath) && self::containsTenantPlaceholder(self::$internalStorePath);
    }

    private static function containsTenantPlaceholder(string $path): bool {
        return str_contains($path, '{tenant}');
    }
}

<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https:\/\/schuppelius.org
 * Filename     : DmsMapper.php
 * License      : MIT License
 * License Uri  : https:\/\/opensource.org\/license\/mit
 */

namespace App\Helper;

use App\Config\Config;
use App\Factories\StorageFactory;
use Datev\Entities\ClientMasterData\Clients\Client;

class InternalStoreMapper {
    public static function getInternalStorePath(Client $client, string $datevDMSCategory, ?string $parameter = null): ?string {
        $config = Config::getInstance();
        $datevDMSMapping = $config->getDatevDMSMapping();

        if (!array_key_exists($datevDMSCategory, $datevDMSMapping)) {
            return null;
        }

        $internalStorePath = StorageFactory::getInternalStorePathForClient($client);
        if ($internalStorePath === null) {
            return null;
        }

        return self::validatePath(
            self::buildInternalStorePath($internalStorePath, $datevDMSMapping[$datevDMSCategory], $parameter)
        );
    }

    public static function requiresPattern(string $internalPath, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (strpos($internalPath, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function requiresYear(string $internalPath): bool {
        $config = Config::getInstance();
        return self::requiresPattern($internalPath, $config->getPerYear());
    }

    public static function requiresPeriod(string $internalPath): bool {
        $config = Config::getInstance();
        return self::requiresPattern($internalPath, $config->getPerPeriod());
    }

    private static function buildInternalStorePath(string $basePath, string $mappedPath, ?string $parameter): string {
        $path = $basePath . '/' . $mappedPath;

        if ($parameter !== null && strpos($path, '%s') !== false) {
            $path = sprintf($path, $parameter);
        }

        return $path;
    }

    private static function validatePath(string $path): ?string {
        $realPath = realpath($path);
        return $realPath !== false ? $realPath : null;
    }
}

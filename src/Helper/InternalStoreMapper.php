<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DmsMapper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper;

use App\Config\Config;
use App\Contracts\Abstracts\HelperAbstract;
use App\Factories\StorageFactory;
use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;

class InternalStoreMapper extends HelperAbstract {
    public static function getInternalStorePath(Client $client, Document $document, ?string $parameter = null): ?string {
        self::setLogger();

        $config = Config::getInstance();
        $datevDMSMapping = $config->getDatevDMSMapping();
        $datevDMSCategory = $document->getFolder()->getName() . " " . $document->getRegister()->getName();

        self::$logger->debug("Mapping für: '$datevDMSCategory' beginnt.");
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
        self::setLogger();

        self::$logger->debug("Wird Pattern für: $internalPath (Pattern: " . print_r(implode(", ", $patterns), true) . ") benötigt.");

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
        self::setLogger();

        $path = $basePath . '/' . $mappedPath;

        if ($parameter !== null && strpos($path, '%s') !== false) {
            $path = sprintf($path, $parameter);
        }
        self::$logger->debug("Pfad für Internen Bereich (ermittelt): '$path'.");

        return $path;
    }

    private static function validatePath(string $path): ?string {
        self::setLogger();

        $realPath = realpath($path);
        self::$logger->debug("Pfad für Internen Bereich (validiert): '$realPath'.");

        return $realPath !== false ? $realPath : null;
    }
}

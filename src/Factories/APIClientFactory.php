<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : APIClientFactory.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Factories;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use Datev\API\Desktop\ClientBasicAuth;
use App\Config\Config;

class APIClientFactory {
    private static ?ApiClientInterface $client = null;

    public static function getClient(): ApiClientInterface {
        if (self::$client === null) {
            $config = Config::getInstance();
            self::$client = new ClientBasicAuth($config->getUser(), $config->getPassword(), $config->getResourceUrl() ?? "https://127.0.0.1:58452", LoggerFactory::getLogger());
        }
        return self::$client;
    }
}

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

use APIToolkit\API\Authentication\BasicAuthentication;
use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use Datev\API\Desktop\Client;
use App\Config\Config;
use RuntimeException;

class APIClientFactory {
    private static ?ApiClientInterface $client = null;

    public static function getClient(): ApiClientInterface {
        if (self::$client === null) {
            $config = Config::getInstance();

            $user = $config->getUser();
            $password = $config->getPassword();
            if (is_null($user) || is_null($password)) {
                throw new RuntimeException("Unvollständige DATEV-API-Konfiguration: 'DatevAPI.user' und 'DatevAPI.password' müssen gesetzt sein.");
            }

            $authentication = new BasicAuthentication($user, $password);
            self::$client = new Client($authentication, $config->getResourceUrl() ?? "https://127.0.0.1:58452", LoggerFactory::getLogger(), false, $config->getVerifySSL());
        }
        return self::$client;
    }
}

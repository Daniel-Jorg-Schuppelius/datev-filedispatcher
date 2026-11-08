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
use App\Config\Config;
use Datev\API\Desktop\Client;
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
            $client = new Client($authentication, $config->getResourceUrl() ?? "https://127.0.0.1:58452", LoggerFactory::getLogger(), false, $config->getVerifySSL());

            $caFile = $config->getCaFile();
            if (!is_null($caFile)) {
                self::applyCaFile($client, $caFile);
            }

            self::$client = $client;
        }
        return self::$client;
    }

    /**
     * Vertraut der CA-Datei, z. B. dem selbstsignierten Zertifikat von DATEVconnect, statt die
     * Zertifikatsprüfung abzuschalten. Setzt php-api-toolkit mit ClientAbstract::setCaBundle() voraus.
     */
    private static function applyCaFile(ApiClientInterface $client, string $caFile): void {
        if (!method_exists($client, 'setCaBundle')) {
            throw new RuntimeException("Die Option 'DatevAPI.caFile' benötigt eine Version von daniel-jorg-schuppelius/php-api-toolkit mit ClientAbstract::setCaBundle(). Bitte die Abhängigkeiten aktualisieren (composer update).");
        }

        $client->setCaBundle($caFile);
    }
}

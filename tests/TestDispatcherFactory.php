<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TestAPIClientFactory.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests;

use Datev\Entities\ClientMasterData\Clients\ID\ClientID;
use Datev\Entities\DocumentManagement\CorrespondencePartners\CorrespondencePartnerGUID;
use Tests\Config\Config;

class TestDispatcherFactory {
    private static ?ClientID $id = null;
    private static ?int $number = null;
    private static ?CorrespondencePartnerGUID $correspondence_partner_guid = null;

    public static function getCorrespondencePartnerGuid(): CorrespondencePartnerGUID {
        if (self::$correspondence_partner_guid === null) {
            $config = new Config();
            self::$correspondence_partner_guid = new CorrespondencePartnerGUID($config->correspondence_partner_guid);
        }
        return self::$correspondence_partner_guid;
    }

    public static function getNumber(): int {
        if (self::$number === null) {
            $config = new Config();
            self::$number = $config->number;
        }
        return self::$number;
    }

    public static function getID(): ClientID {
        if (self::$id === null) {
            $config = new Config();
            self::$id = new ClientID($config->id);
        }
        return self::$id;
    }
}

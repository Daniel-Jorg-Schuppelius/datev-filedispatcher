<?php
/*
 * Created on   : Mon Oct 07 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HelperAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts;

use App\Factories\LoggerFactory;
use CommonToolkit\Contracts\Abstracts\HelperAbstract as CommonHelperAbstract;
use Psr\Log\LoggerInterface;

abstract class HelperAbstract extends CommonHelperAbstract {
    public static function setLogger(?LoggerInterface $logger = null): void {
        if (!is_null($logger)) {
            self::$logger = $logger;
        } elseif (self::$logger != LoggerFactory::getLogger()) {
            self::$logger = LoggerFactory::getLogger();
        }
    }
}

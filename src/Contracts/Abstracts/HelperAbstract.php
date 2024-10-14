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

use App\Contracts\Interfaces\HelperInterface;
use App\Factories\LoggerFactory;
use Psr\Log\LoggerInterface;

abstract class HelperAbstract implements HelperInterface {
    protected static ?LoggerInterface $logger = null;

    public static function setLogger(?LoggerInterface $logger = null): void {
        if (!is_null($logger)) {
            self::$logger = $logger;
        } elseif (is_null(self::$logger)) {
            self::$logger = LoggerFactory::getLogger();
        }
    }

    public static function sanitize(string $filename): string {
        return preg_replace("/ |'|\(|\)/", '\\\${0}', $filename);
    }
}

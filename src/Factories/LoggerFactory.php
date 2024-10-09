<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : APIClientFactory.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Factories;

use APIToolkit\Logger\ConsoleLogger;
use APIToolkit\Factories\ConsoleLoggerFactory as BaseConsoleLoggerFactory;
use Psr\Log\LoggerInterface;

class LoggerFactory extends BaseConsoleLoggerFactory {

    public static function getLogger(): LoggerInterface {
        if (self::$logger === null) {
            self::$logger = new ConsoleLogger();
        }
        return static::$logger;
    }
}

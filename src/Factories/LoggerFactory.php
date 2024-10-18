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

use APIToolkit\Contracts\Interfaces\LoggerFactoryInterface;
use APIToolkit\Logger\ConsoleLogger;
use APIToolkit\Logger\FileLogger;
use App\Config\Config;
use App\Enums\LogType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerFactory implements LoggerFactoryInterface {
    public static ?LoggerInterface $logger = null;

    public static function getLogger(): LoggerInterface {
        if (self::$logger === null) {
            $config = Config::getInstance();
            if ($config->isDebugEnabled() || $config->getLogType() === LogType::CONSOLE) {
                self::$logger = new ConsoleLogger();
            } elseif ($config->getLogType() === LogType::FILE) {
                self::$logger = new FileLogger($config->getLogPath());
            } else {
                self::$logger = new NullLogger();
            }
        }
        return static::$logger;
    }
}

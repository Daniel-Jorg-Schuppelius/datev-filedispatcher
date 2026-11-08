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

use App\Config\Config;
use ERRORToolkit\Contracts\Interfaces\LoggerFactoryInterface;
use ERRORToolkit\Enums\LogType;
use ERRORToolkit\Logger\{ConsoleLogger, FileLogger};
use Psr\Log\{LoggerInterface, NullLogger};

class LoggerFactory implements LoggerFactoryInterface {
    public static ?LoggerInterface $logger = null;

    public static function getLogger(): LoggerInterface {
        if (self::$logger === null) {
            $config = Config::getInstance();
            if ($config->isDebugEnabled() || $config->getLogType() === LogType::CONSOLE) {
                self::$logger = new ConsoleLogger($config->getLogLevel());
            } elseif ($config->getLogType() === LogType::FILE) {
                self::$logger = new FileLogger($config->getLogPath(), $config->getLogLevel());
            } else {
                self::$logger = new NullLogger;
            }
        }
        return self::$logger;
    }

    public static function resetLogger(): void {
        self::$logger = null;
    }
}

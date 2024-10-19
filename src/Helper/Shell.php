<?php
/*
 * Created on   : Mon Oct 07 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Shell.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper;

use App\Contracts\Abstracts\HelperAbstract;
use Exception;

class Shell extends HelperAbstract {
    public static function executeShellCommand(string $command, array &$output = [], int &$resultCode = 0, bool $throwException = false, int $expectedResultCode = 0): bool {
        self::setLogger();

        exec($command, $output, $resultCode);

        if ($resultCode !== $expectedResultCode) {
            if ($throwException) {
                self::$logger->error("Fehler bei der Ausführung des Kommandos: $command");
                throw new Exception("Fehler bei der Ausführung des Kommandos: " . implode("\n", $output));
            } else {
                self::$logger->warning("Kommando gab unerwartete Ausgabe zurück (keine Exception geworfen): $command");
                return false;
            }
        }

        self::$logger->debug("Kommando erfolgreich ausgeführt: $command");
        return true;
    }

    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand): string {
        return PHP_OS_FAMILY === 'Windows' ? $windowsCommand : $unixCommand;
    }
}

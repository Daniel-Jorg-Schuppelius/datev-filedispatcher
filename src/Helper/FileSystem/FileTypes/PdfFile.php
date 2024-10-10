<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PdfFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Helper\FileSystem\FileTypes;

use App\Contracts\Abstracts\HelperAbstract;
use App\Helper\Shell;
use Exception;

class PdfFile extends HelperAbstract {
    public static function getMetaData(string $filename): array {
        $command = sprintf("pdfinfo %s", escapeshellarg($filename));
        $output = [];
        $resultCode = 0;

        Shell::executeShellCommand($command, $output, $resultCode);

        if ($resultCode !== 0) {
            throw new Exception("Fehler beim Abrufen der PDF-Metadaten für $filename");
        }

        $metadata = [];
        foreach ($output as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $metadata[trim($key)] = trim($value);
            }
        }

        return $metadata;
    }

    public static function isEncrypted(string $filename): bool {
        $metadata = self::getMetaData($filename);
        return isset($metadata['Encrypted']) && $metadata['Encrypted'] === 'yes';
    }

    public static function isValid(string $filename): bool {
        $command = Shell::getPlatformSpecificCommand(
            sprintf("mutool info %s 2>&1 | grep error", escapeshellarg($filename)),
            sprintf('pdfinfo %s 2>&1 | findstr /R "Syntax.Error" || exit /b 0', escapeshellarg($filename))
        );
        $output = [];
        $resultCode = 0;

        Shell::executeShellCommand($command, $output, $resultCode);

        return $resultCode === 0 && empty($output);
    }
}

<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FilenameSanitizer.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper;

/**
 * Bereinigt Zieldateinamen. Teile davon stammen aus der DATEV-API (z. B. Namen von
 * Mitarbeitern) und dürfen weder Verzeichnisse erzeugen noch Zeichen enthalten,
 * die Windows-Freigaben oder Nextcloud ablehnen.
 */
final class FilenameSanitizer {
    private const REPLACEMENT = '_';

    /** Pfadtrenner, unter Windows/Nextcloud verbotene Zeichen und Steuerzeichen */
    private const FORBIDDEN_CHARACTERS = '/[\/\\\\:*?"<>|\x00-\x1F\x7F]/';

    public static function sanitize(string $filename): string {
        $sanitized = preg_replace(self::FORBIDDEN_CHARACTERS, self::REPLACEMENT, $filename) ?? $filename;

        // Windows erlaubt keine Punkte oder Leerzeichen am Ende, führende Punkte machen Dateien unsichtbar.
        $sanitized = trim($sanitized, " .");

        return $sanitized === '' ? self::REPLACEMENT : $sanitized;
    }
}

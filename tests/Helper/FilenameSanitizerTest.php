<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FilenameSanitizerTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use App\Helper\FilenameSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilenameSanitizerTest extends TestCase {
    /**
     * @return array<string, array{string, string}>
     */
    public static function filenameProvider(): array {
        return [
            'unverändert' => ['Entgeltabrechnung-00001_Müller_Jörg.pdf', 'Entgeltabrechnung-00001_Müller_Jörg.pdf'],
            'Klammern und Leerzeichen bleiben' => ['Einzelaufstellung - Unfallversicherung (jährlich).pdf', 'Einzelaufstellung - Unfallversicherung (jährlich).pdf'],
            'Pfadtrenner' => ['Lohnkonto-00001_../../etc_passwd.pdf', 'Lohnkonto-00001_.._.._etc_passwd.pdf'],
            'Backslash' => ['Lohnkonto-00001_a\\b.pdf', 'Lohnkonto-00001_a_b.pdf'],
            'Windows-Sonderzeichen' => ['a:b*c?d"e<f>g|h.pdf', 'a_b_c_d_e_f_g_h.pdf'],
            'Steuerzeichen' => ["Name\nmit\tUmbruch.pdf", 'Name_mit_Umbruch.pdf'],
            'Punkte und Leerzeichen am Rand' => [' .datei.pdf. ', 'datei.pdf'],
            'leer' => ['', '_'],
        ];
    }

    #[DataProvider('filenameProvider')]
    public function test_sanitize(string $filename, string $expected): void {
        $this->assertSame($expected, FilenameSanitizer::sanitize($filename));
    }
}

<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Contracts\Interfaces\FileServiceInterface;

class PayrollFileService implements FileServiceInterface {
    private $filename;

    public function __construct(string $filename) {
        $this->filename = $filename;
    }

    public static function getPattern(): string {
        return '/^(\d{5})_(\d{5})_([A-Za-z]+_[A-Za-z]+)_(\d{2})_(\d{4})_Brutto_Netto_([A-Z0-9]{2,3})\.pdf$/';
    }

    public static function matchesPattern(string $filename): bool {
        return preg_match(self::getPattern(), basename($filename)) === 1;
    }

    public function process(): void {
        // TODO: Implementiere die Logik für die Verarbeitung der Datei
        echo "Verabreite Payroll Datei: $this->filename";
    }
}

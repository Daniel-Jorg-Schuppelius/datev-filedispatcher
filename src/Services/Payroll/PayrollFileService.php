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

use App\Contracts\Abstracts\FileServiceAbstract;

class PayrollFileService extends FileServiceAbstract {
    private string $month;
    private string $year;

    public function __construct(string $filename) {
        parent::__construct($filename);
        $this->filename = $filename;
    }

    public static function getPattern(): string {
        return '/^(\d{5})_(\d{5})_([A-Za-z]+_[A-Za-z]+)_(\d{2})_(\d{4})_Brutto_Netto_([A-Z0-9]{2,3})\.pdf$/';
    }

    public function process(): void {
        // TODO: Implementiere die Logik für die Verarbeitung der Datei
        echo "Verabreite Payroll Datei: $this->filename";
        $this->extractDatafromFilename();
    }

    protected function extractDatafromFilename(): void {
        $matches = [];
        self::matchesPattern($this->filename, $matches);
        $this->documentNumber = $matches[1];
        $this->month = $matches[4];
        $this->year = $matches[5];
    }
}

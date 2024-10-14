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

use App\Contracts\Abstracts\PeriodicFileServiceAbstract;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PayrollFileService extends PeriodicFileServiceAbstract {
    public function __construct(string $filename, LoggerInterface $logger = null) {
        parent::__construct($filename, null, $logger);
    }

    public static function getPattern(): string {
        return '/^(\d{5})_(\d{5})_([A-Za-z]+_[A-Za-z]+)_(\d{2})_(\d{4})_Brutto_Netto_([A-Z0-9]{2,3})\.pdf$/';
    }

    public static function getSubFolder(): string {
        return "02 Entgeltabrechnung";
    }

    public function process(): void {
        $this->logger->info("Verarbeite Payroll Datei: {$this->filename}");
    }

    protected function extractDataFromFilename(): void {
        $matches = [];
        if (!self::matchesPattern($this->filename, $matches)) {
            $this->logger->error("Ungültiger Dateiname: {$this->filename}");
            throw new InvalidArgumentException("Der Dateiname entspricht nicht dem erwarteten Muster: {$this->filename}");
        }

        $this->client = $this->clientsEndpoint->search(["filter" => "number eq $matches[1]"])->getFirstValue();
        if (is_null($this->client)) {
            $this->logger->error("Client konnte nicht gefunden werden: $matches[1]");
            throw new RuntimeException("Client konnte nicht gefunden werden: $matches[1]");
        }
        $this->validateDate((int) $matches[5], (int) $matches[4]);
    }
}

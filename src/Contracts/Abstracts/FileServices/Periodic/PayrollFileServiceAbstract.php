<?php

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices\Periodic;

use App\Contracts\Abstracts\FileServices\PeriodicFileServiceAbstract;

abstract class PayrollFileServiceAbstract extends PeriodicFileServiceAbstract {
    protected const SUBFOLDER = "02 Entgeltabrechnung";

    protected function extractDataFromFilename(): void {
        $matches = $this->getMatches();

        if (array_key_exists("tenant", $matches)) {
            $this->setClient($matches["tenant"]);
        }

        if (array_key_exists("year", $matches) && array_key_exists("month", $matches)) {
            $this->setDate((int) $matches["year"], (int) $matches["month"]);
        }
    }
}

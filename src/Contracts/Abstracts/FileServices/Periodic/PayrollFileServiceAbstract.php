<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollFileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices\Periodic;

use App\Contracts\Abstracts\FileServices\PeriodicFileServiceAbstract;
use DateTime;

abstract class PayrollFileServiceAbstract extends PeriodicFileServiceAbstract {
    protected const SUBFOLDER = "02 Entgeltabrechnung";

    protected function extractDataFromFile(): void {
        $matches = $this->getMatches();

        // Die Periode zuerst setzen, sie bestimmt den Stichtag der Payroll-Abfragen.
        if (array_key_exists("year", $matches) && array_key_exists("month", $matches)) {
            $this->setDate((int) $matches["year"], (int) $matches["month"]);
        }

        if (array_key_exists("tenant", $matches)) {
            $this->setClients($matches["tenant"], $this->getPayrollReferenceDate());
        }
    }

    /**
     * Stichtag für die Payroll-Abfragen: das Monatsende der Abrechnungsperiode,
     * damit Mandant und Mitarbeiter mit dem damals gültigen Stand geladen werden.
     * Für die laufende oder eine künftige Periode (oder ohne Periode) null = heute.
     */
    protected function getPayrollReferenceDate(): ?DateTime {
        if (!isset($this->date)) {
            return null;
        }

        $referenceDate = (clone $this->date)->modify('last day of this month');

        return $referenceDate < new DateTime('today') ? $referenceDate : null;
    }
}

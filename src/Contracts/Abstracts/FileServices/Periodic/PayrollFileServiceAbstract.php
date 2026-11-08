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
use RuntimeException;

/**
 * Basis der Lohn-Auswertungen. Für die meisten Services genügen PATTERN und
 * DOCUMENT_TYPE: Der Zieldateiname lautet dann "<DOCUMENT_TYPE>.<Endung>" bzw. mit
 * einer Gruppe "employee" im Pattern "<DOCUMENT_TYPE>-<Nr>_<Nachname>_<Vorname>.<Endung>".
 */
abstract class PayrollFileServiceAbstract extends PeriodicFileServiceAbstract {
    protected const SUBFOLDER = "02 Entgeltabrechnung";

    /** Mandant_Monat_Jahr_ am Anfang der meisten Lohn-Dateinamen, z. B. "20542_10_2024_" */
    protected const PERIOD_PREFIX = '(?<tenant>\d{5})_(?<month>\d{2})_(?<year>\d{4})_';

    /** Kennung der Auswertung am Ende, z. B. "_AA0" */
    protected const EVALUATION_SUFFIX = '_([A-Z0-9]{2,3})';

    /** Mitarbeiternummer, wird für den Zieldateinamen über die Payroll-Daten aufgelöst */
    protected const EMPLOYEE = '(?<employee>\d{5})';

    /** Dokumenttyp im Zieldateinamen, leer = Dateiname bleibt erhalten */
    protected const DOCUMENT_TYPE = '';

    /** Endung des Zieldateinamens */
    protected const DESTINATION_EXTENSION = 'pdf';

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

    protected function getDestinationFilename(): string {
        if (static::DOCUMENT_TYPE === '') {
            return parent::getDestinationFilename();
        }

        $matches = $this->getMatches();
        if (isset($matches['employee'])) {
            return static::DOCUMENT_TYPE . '-' . $this->getEmployeeFilenamePart($matches['employee']) . '.' . static::DESTINATION_EXTENSION;
        }

        return static::DOCUMENT_TYPE . '.' . static::DESTINATION_EXTENSION;
    }

    /**
     * Mitarbeiter-Teil des Zieldateinamens: "<Nr>_<Nachname>_<Vorname>".
     */
    protected function getEmployeeFilenamePart(string $employeeNumber): string {
        $payrollClient = $this->payrollClient;
        if (is_null($payrollClient)) {
            self::logErrorAndThrow(RuntimeException::class, "Client (Payroll) nicht gefunden für die Datei: {$this->getFilename()}");
        }

        $employees = $payrollClient->getEmployees();
        if (is_null($employees)) {
            self::logErrorAndThrow(RuntimeException::class, "Keine Mitarbeiter für Client: {$payrollClient->getNumber()} gefunden");
        }

        $employee = $employees->getFirstValue('id', $employeeNumber);
        if (is_null($employee)) {
            self::logErrorAndThrow(RuntimeException::class, "Mitarbeiter nicht gefunden: {$employeeNumber}");
        }

        // Personenbezogene Daten nur im Debug-Log.
        $this->logDebug("Mitarbeiter gefunden: {$employeeNumber} {$employee->getSurname()} {$employee->getFirstName()}");

        return "{$employeeNumber}_{$employee->getSurname()}_{$employee->getFirstName()}";
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

<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PeriodicFileServiceTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App\Traits;

use APIToolkit\Enums\Month;
use DateTime;
use Exception;
use OutOfRangeException;

trait PeriodicFileServiceTrait {
    protected DateTime $date;

    public function getMonth(): int {
        return (int)$this->date->format('n');
    }

    public function getYear(): int {
        return (int)$this->date->format('Y');
    }

    protected function getFormattedDateParts(bool $leadingZero): array {
        $yearFormatted = $this->date->format('Y');
        $monthValue = $this->getMonth();
        $monthFormatted = ($leadingZero ? $this->date->format('m') : $monthValue) . " " . Month::toArray(false, 'de')[$monthValue];

        return [$this->adjustForPreviousYears($yearFormatted), $monthFormatted];
    }

    protected function prepareSubFolder(string $subFolder, bool $requiresPeriod, bool $requiresYear): string {
        if (($requiresPeriod || $requiresYear) && strpos($subFolder, '%s') === false) {
            $subFolder .= DIRECTORY_SEPARATOR . "%s";
        }
        return $subFolder;
    }

    protected function adjustForPreviousYears(string $yearFormatted): string {
        $minYearValue = new DateTime();
        $minYearValue->modify("-" . $this->config->getPreviousYears4Internal() . " years");

        if ($this->date < $minYearValue) {
            $this->logDebug("Datum liegt vor dem Mindestjahr: " . $minYearValue->format('Y') . " für die Datei: {$this->file}, wird auf Vorjahresordner gesetzt.");
            $yearFormatted = $this->config->getPreviousYearsFolderName4Internal() . DIRECTORY_SEPARATOR . $yearFormatted;
        }

        return $yearFormatted;
    }

    protected function setDate(?int $year, ?int $month = null, ?int $day = null): void {
        $year = $year ?? (int) (new DateTime())->format('Y');
        match ($month) {
            41 => $month = 3,
            42 => $month = 6,
            43 => $month = 9,
            44 => $month = 12,
            default => $month = $month ?? 1
        };
        $day = $day ?? 1;

        try {
            $this->date = new DateTime("$year-$month-$day");
        } catch (Exception) {
            $this->logError("Ungültiges Datum: $year-$month im Dateinamen: {$this->filename}");
            throw new OutOfRangeException("Ungültiges Datum: $year-$month im Dateinamen: {$this->filename}");
        }
    }

    protected function validateConfig(): void {
        parent::validateConfig();

        if (is_null($this->config->getPreviousYears4Internal())) {
            $this->logError("Ungültige Konfiguration für die Anzahl der Vorjahre.");
            throw new OutOfRangeException("Ungültige Konfiguration für die Anzahl der Vorjahre.");
        } elseif (is_null($this->config->getPreviousYearsFolderName4Internal())) {
            $this->logError("Ungültige Konfiguration für den Namen des Vorjahresordners.");
            throw new OutOfRangeException("Ungültige Konfiguration für den Namen des Vorjahresordners.");
        }
    }
}

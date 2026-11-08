<?php
/*
 * Created on   : Sat Oct 19 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PeriodicFileServiceTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Traits;

use App\Helper\InternalStoreMapper;
use CommonToolkit\Enums\Month;
use DateTime;
use Exception;
use OutOfRangeException;

/**
 * Ablage nach Jahr bzw. Monat. Ob ein Unterordner jahres- oder monatsweise abgelegt
 * wird, steht in der Konfiguration (PerYear / PerPeriod). Mit FORCE_YEARLY_STORAGE
 * legt ein Service unabhängig davon immer im Jahresordner ab.
 */
trait PeriodicFileServiceTrait {
    /** true = immer Jahresablage, unabhängig von PerYear / PerPeriod */
    protected const FORCE_YEARLY_STORAGE = false;

    protected DateTime $date;

    /**
     * Zielordner, wenn der Unterordner weder jahres- noch monatsweise abgelegt wird.
     */
    abstract protected function getNonPeriodicDestinationFolder(string $subFolder): ?string;

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        $subFolder = $this->getSubFolder();

        if (static::FORCE_YEARLY_STORAGE) {
            $requiresPeriod = false;
            $requiresYear = true;
        } else {
            $requiresPeriod = InternalStoreMapper::requiresPeriod($subFolder);
            $requiresYear = InternalStoreMapper::requiresYear($subFolder);
        }

        if (!$requiresPeriod && !$requiresYear) {
            return $this->getNonPeriodicDestinationFolder($subFolder);
        }

        $subFolder = $this->prepareSubFolder($subFolder, $requiresPeriod, $requiresYear);

        $client = $this->client;
        if (is_null($client)) {
            $this->logError("Kein Client gesetzt, der Zielordner für '" . $subFolder . "' kann nicht bestimmt werden.");
            return null;
        }

        [$yearFormatted, $monthFormatted] = $this->getFormattedDateParts($leadingZero);

        if ($requiresPeriod) {
            $this->logInfo("Nutze Monatsablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($client, $subFolder, $yearFormatted . DIRECTORY_SEPARATOR . $monthFormatted);
        }

        $this->logInfo("Nutze Jahresablage für den Ordner '" . $subFolder . "'.");
        return InternalStoreMapper::getInternalStorePath($client, $subFolder, $yearFormatted);
    }

    public function getMonth(): int {
        return (int) $this->date->format('n');
    }

    public function getYear(): int {
        return (int) $this->date->format('Y');
    }

    /**
     * @return array{string, string} Jahr und Monat, jeweils formatiert
     */
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
        $minYearValue = new DateTime;
        $minYearValue->modify("-" . $this->config->getPreviousYears4Internal() . " years");

        if ($this->date < $minYearValue) {
            $this->logDebug("Datum liegt vor dem Mindestjahr: " . $minYearValue->format('Y') . " für die Datei: {$this->file}, wird auf Vorjahresordner gesetzt.");
            $yearFormatted = $this->config->getPreviousYearsFolderName4Internal() . DIRECTORY_SEPARATOR . $yearFormatted;
        }

        return $yearFormatted;
    }

    protected function setDate(?int $year, ?int $month = null, ?int $day = null): void {
        $year = $year ?? (int) (new DateTime)->format('Y');
        // Quartale (41-44) werden auf den letzten Monat des Quartals abgebildet.
        $month = match ($month) {
            41 => 3,
            42 => 6,
            43 => 9,
            44 => 12,
            default => $month ?? 1
        };
        $day = $day ?? 1;

        try {
            $this->date = new DateTime("$year-$month-$day");
        } catch (Exception) {
            self::logErrorAndThrow(OutOfRangeException::class, "Ungültiges Datum: $year-$month im Dateinamen: {$this->getFilename()}");
        }
    }

    protected function validateConfig(): void {
        parent::validateConfig();

        if (is_null($this->config->getPreviousYears4Internal())) {
            self::logErrorAndThrow(OutOfRangeException::class, "Ungültige Konfiguration für die Anzahl der Vorjahre.");
        } elseif (is_null($this->config->getPreviousYearsFolderName4Internal())) {
            self::logErrorAndThrow(OutOfRangeException::class, "Ungültige Konfiguration für den Namen des Vorjahresordners.");
        }
    }
}

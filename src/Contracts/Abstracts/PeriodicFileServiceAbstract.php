<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use APIToolkit\Enums\Month;
use App\Config\Config;
use App\Helper\InternalStoreMapper;
use DateTime;
use Exception;
use OutOfRangeException;
use Psr\Log\LoggerInterface;

abstract class PeriodicFileServiceAbstract extends FileServiceAbstract {
    protected DateTime $date;

    public function __construct(string $filename, ?ApiClientInterface $client = null, ?LoggerInterface $logger = null) {
        parent::__construct($filename, $client, $logger);
    }

    public function getMonth(): int {
        return (int)$this->date->format('n');
    }

    public function getYear(): int {
        return (int)$this->date->format('Y');
    }

    public function getDestinationFolder(bool $leadingZero = true): ?string {
        $subFolder = static::getSubFolder();
        $requiresPeriod = InternalStoreMapper::requiresPeriod($subFolder);
        $requiresYear = InternalStoreMapper::requiresYear($subFolder);

        $config = Config::getInstance();

        if (is_null($config->getPreviousYears4Internal())) {
            throw new OutOfRangeException("Ungültige Konfiguration für die Anzahl der Vorjahre.");
        } elseif (is_null($config->getPreviousYearsFolderName4Internal())) {
            throw new OutOfRangeException("Ungültige Konfiguration für den Namen des Vorjahresordners.");
        }

        $minYearValue = (clone $this->date)->modify("-" . $config->getPreviousYears4Internal() . " years");

        $yearFormatted = $this->date->format('Y');
        $monthValue = $this->getMonth();
        $monthFormatted = ($leadingZero ? $this->date->format('m') : $monthValue) . " " . Month::toArray(false, 'de')[$monthValue];

        if (($requiresPeriod || $requiresYear) && strpos($subFolder, '%s') === false) {
            $subFolder .= DIRECTORY_SEPARATOR . "%s";
        }

        if ($this->date < $minYearValue) {
            $yearFormatted = $config->getPreviousYearsFolderName4Internal() . DIRECTORY_SEPARATOR . $yearFormatted;
        }

        if ($requiresPeriod) {
            $this->logger->info("Nutze Monatsablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted . DIRECTORY_SEPARATOR . $monthFormatted);
        } elseif ($requiresYear) {
            $this->logger->info("Nutze Jahresablage für den Ordner '" . $subFolder . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, $subFolder, $yearFormatted);
        }

        $this->logger->warning("Keine Konfiguration für eine periodische Ablage in den Ordner '" . $subFolder . "' gefunden.");
        return null;
    }

    protected function validateDate(int $year, int $month = 1, int $day = 1): void {
        try {
            $this->date = new DateTime("$year-$month-$day");
        } catch (Exception $e) {
            $this->logger->error("Ungültiges Datum: $year-$month im Dateinamen: {$this->filename}");
            throw new OutOfRangeException("Ungültiges Datum: $year-$month im Dateinamen: {$this->filename}");
        }
    }

    abstract protected static function getSubFolder(): string;
}

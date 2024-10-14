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
        $monthValue = $this->getMonth();
        $monthFormatted = ($leadingZero ? $this->date->format('m') : $monthValue) . " " . Month::toArray(false, 'de')[$monthValue];

        if (InternalStoreMapper::requiresPeriod(static::getSubFolder())) {
            $this->logger->info("Nutze Monatsablage für den Ordner '" . static::getSubFolder() . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, static::getSubFolder() . "/%s", $this->date->format('Y') . DIRECTORY_SEPARATOR . $monthFormatted);
        } elseif (InternalStoreMapper::requiresYear(static::getSubFolder())) {
            $this->logger->info("Nutze Jahresablage für den Ordner '" . static::getSubFolder() . "'.");
            return InternalStoreMapper::getInternalStorePath($this->client, static::getSubFolder() . "/%s", $this->date->format('Y'));
        }

        $this->logger->warning("Keine Konfiguration für eine periodische Ablage in den Ordner '" . static::getSubFolder() . "' gefunden.");
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

<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileDispatcher.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Helper;

use App\Config\Config;
use App\Contracts\Interfaces\FileServices\{FileHandlerInterface, FileServiceInterface, PreProcessFileServiceInterface};
use App\Enums\PreProcessResult;
use App\Factories\LoggerFactory;
use Closure;
use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\FileTypes\{CsvFile, PdfFile, TifFile, XmlFile, ZipFile};
use ConfigToolkit\ClassLoader;
use ERRORToolkit\LoggerRegistry;
use Exception;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class FileDispatcher extends HelperAbstract {
    protected static string $servicesDirectory = __DIR__ . '/../Services';
    protected static string $preProcessServicesDirectory = __DIR__ . '/../PreProcessServices';
    protected static string $servicesNamespace = 'App\\Services';
    protected static string $preProcessNamespace = 'App\\PreProcessServices';

    /** @var list<class-string<FileServiceInterface>>|null */
    protected static ?array $services = null;

    /** @var list<class-string<PreProcessFileServiceInterface>>|null */
    protected static ?array $preProcessServices = null;

    /** @var list<string> */
    protected static array $fileTypesWithoutGenericPreProcessing = ['xlsm', 'txt'];

    /**
     * Dateien, die nie verarbeitet werden: Logdateien (auch rotierte), Lockfiles und die
     * Anleitungen zu Arbeitnehmer online, die der EmployeeInvitationFileService mit ablegt.
     */
    protected const IGNORED_FILENAME_PATTERNS = [
        '/^log\.txt(\.\d{8}_\d{6})?$/i',
        '/Arbeitnehmer online\.docx$/i',
        '/\.lock$/i',
    ];

    public static function processFile(string $file): void {
        LoggerRegistry::setLogger(LoggerFactory::getLogger());

        if (empty($file)) {
            self::logWarning("Keine Datei zur Verarbeitung übergeben.");
            return;
        }

        if (self::isIgnoredFile($file)) {
            self::logDebug("Die Datei $file wird grundsätzlich nicht verarbeitet.");
            return;
        }

        $excludedFolder = self::getExcludingFolder($file);
        if (!is_null($excludedFolder)) {
            self::logInfo("Die Datei $file liegt im ausgeschlossenen Ordner $excludedFolder und wird nicht verarbeitet.");
            return;
        }

        if (!File::exists($file)) {
            self::logWarning("Datei $file existiert nicht.");
            return;
        }

        // Prüfe auf temporäre oder ungültige Dateien
        if (self::isTemporaryOrInvalidFile($file)) {
            return;
        }

        try {
            if (!self::preProcessFile($file)) {
                return;
            }

            // Die Vorverarbeitung löscht die Quelldatei in manchen Fällen (TifFile::convertToPdf
            // entfernt die TIFF-Datei nach der Umwandlung). Die Umwandlung läuft über externe
            // Programme, deren Änderungen PHPs stat-Cache nicht automatisch verwerfen.
            clearstatcache(true, $file);
            if (!File::exists($file)) {
                self::logDebug("Datei $file wurde durch die Vorverarbeitung ersetzt oder entfernt.");
                return;
            }

            $serviceClass = self::resolveServiceClass($file);
            if (is_null($serviceClass)) {
                self::logWarning("Kein passender Service für Datei: $file gefunden.");
                return;
            }

            self::logDebug("Service: " . $serviceClass . " für Datei: $file gefunden.");
            $service = new $serviceClass($file);
            $service->process();
        } catch (Throwable $e) {
            self::logException($e);
            throw $e;
        }
    }

    /**
     * Liefert den FileService, der die Datei verarbeitet: den ersten passenden nach Priorität.
     *
     * @return class-string<FileServiceInterface>|null
     */
    public static function resolveServiceClass(string $file): ?string {
        self::setServices();

        foreach (self::$services ?? [] as $serviceClass) {
            if ($serviceClass::matchesPattern($file)) {
                return $serviceClass;
            }
        }

        return null;
    }

    /**
     * Dateien, die der Dispatcher nie anfasst (unabhängig von der Konfiguration).
     */
    public static function isIgnoredFile(string $file): bool {
        $basename = basename($file);

        foreach (self::IGNORED_FILENAME_PATTERNS as $pattern) {
            if (preg_match($pattern, $basename) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Noch nicht verarbeitete Dateien unterhalb des Verzeichnisses, z. B. für einen Rescan
     * nach einem Dienststart oder nach Fehlern (DATEV-Server in der Sicherung).
     *
     * @param int $minAgeSeconds nur Dateien, die mindestens so lange unverändert sind
     * @return list<string>
     */
    public static function findPendingFiles(string $directory, int $minAgeSeconds = 0): array {
        $directory = realpath($directory);
        if ($directory === false || !is_dir($directory)) {
            return [];
        }

        // Ausgeschlossene und versteckte Ordner gar nicht erst durchsuchen.
        $filter = static function (SplFileInfo $current): bool {
            if ($current->isDir()) {
                return !str_starts_with($current->getFilename(), '.') && is_null(self::getExcludingFolder($current->getPathname()));
            }
            return true;
        };

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), $filter)
        );

        $threshold = time() - $minAgeSeconds;
        $files = [];
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getMTime() > $threshold) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if (self::isIgnoredFile($path) || !is_null(self::getExcludingFolder($path))) {
                continue;
            }
            if (str_starts_with($fileInfo->getFilename(), '.') || $fileInfo->getExtension() === '' || $fileInfo->getSize() === 0) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    /**
     * Liegt die Datei im Ordner (oder ist sie der Ordner)? Verglichen wird verzeichnisgenau:
     * "/abc" umfasst "/abc/datei.pdf", aber nicht "/abcdef/datei.pdf".
     */
    public static function isInFolder(string $file, string $folder): bool {
        $normalizedFile = self::normalizePath($file);
        $normalizedFolder = self::normalizePath($folder);

        if ($normalizedFolder === '') {
            return false;
        }

        return $normalizedFile === $normalizedFolder || str_starts_with($normalizedFile, $normalizedFolder . '/');
    }

    /**
     * Ausgeschlossener Ordner (ExcludedFolders), in dem die Datei liegt, oder null.
     */
    private static function getExcludingFolder(string $file): ?string {
        foreach (Config::getInstance()->getExcludedFolders() ?? [] as $folder) {
            if (self::isInFolder($file, $folder)) {
                return $folder;
            }
        }

        return null;
    }

    private static function normalizePath(string $path): string {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Prüft ob die Datei eine temporäre oder ungültige Datei ist
     * - 0-Byte Dateien
     * - Dateien ohne Erweiterung (z. B. Windows 8.3 Short Names wie NDH6SA~M)
     * - Versteckte Dateien (beginnend mit .)
     */
    protected static function isTemporaryOrInvalidFile(string $file): bool {
        $basename = pathinfo($file, PATHINFO_BASENAME);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        // 0-Byte Dateien ignorieren
        if (File::exists($file) && filesize($file) === 0) {
            self::logInfo("Überspringe 0-Byte Datei: $file");
            return true;
        }

        // Dateien ohne Erweiterung ignorieren
        if (empty($extension)) {
            self::logInfo("Überspringe Datei ohne Erweiterung: $file");
            return true;
        }

        // Versteckte Dateien ignorieren
        if (str_starts_with($basename, '.')) {
            self::logInfo("Überspringe versteckte Datei: $file");
            return true;
        }

        return false;
    }

    /**
     * Lädt die Service-Klassen einmalig und sortiert sie nach Priorität. Die Sortierung ist
     * stabil, bei gleicher Priorität bleibt die alphabetische Reihenfolge des ClassLoaders.
     *
     * @template T of FileHandlerInterface
     * @param class-string<T> $interface
     * @param list<class-string<T>>|null $serviceStorage
     * @param-out list<class-string<T>> $serviceStorage
     */
    protected static function setServiceClasses(string $directory, string $namespace, string $interface, ?array &$serviceStorage): void {
        if (is_null($serviceStorage)) {
            $classLoader = new ClassLoader($directory, $namespace, $interface, self::$logger);
            $classes = $classLoader->getClasses();
            usort($classes, static fn (string $a, string $b): int => $b::getPriority() <=> $a::getPriority());
            $serviceStorage = $classes;
        }
    }

    protected static function setServices(): void {
        self::setServiceClasses(self::$servicesDirectory, self::$servicesNamespace, FileServiceInterface::class, self::$services);
    }

    protected static function setPreProcessServices(): void {
        self::setServiceClasses(self::$preProcessServicesDirectory, self::$preProcessNamespace, PreProcessFileServiceInterface::class, self::$preProcessServices);
    }

    /**
     * @return bool true = weiter mit den FileServices, false = Datei bleibt liegen
     */
    private static function preProcessFile(string $file): bool {
        self::setPreProcessServices();

        $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        try {
            foreach (self::$preProcessServices ?? [] as $preProcessServiceClass) {
                if (!$preProcessServiceClass::matchesPattern($file)) {
                    continue;
                }

                self::logDebug("PreProcessService: " . $preProcessServiceClass . " für Datei: $file gefunden.");
                $result = (new $preProcessServiceClass($file))->preProcess();

                if ($result === PreProcessResult::Done) {
                    return true;
                } elseif ($result === PreProcessResult::Abort) {
                    self::logInfo("Vorverarbeitung durch $preProcessServiceClass abgebrochen, die Datei $file bleibt liegen.");
                    return false;
                }
                // PreProcessResult::Skipped: nächsten PreProcessService versuchen
            }

            if (in_array($fileType, self::$fileTypesWithoutGenericPreProcessing, true)) {
                self::logNotice("Kein passender preProcessService für Datei: $file gefunden.");
                return true; // Datei benötigt keine generische Vorverarbeitung
            }

            self::logNotice("Kein passender preProcessService für Datei: $file gefunden. Versuche generische Vorverarbeitung.");
            return self::genericPreProcessFile($file, $fileType);
        } catch (Throwable $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Prüft bzw. wandelt die Datei je nach Typ. Fehler werden geloggt, die Datei geht
     * trotzdem an die FileServices. Unbekannte Typen bleiben liegen.
     */
    private static function genericPreProcessFile(string $file, string $fileType): bool {
        return match ($fileType) {
            'csv' => self::validateFile($file, 'CSV-Datei', static fn (): bool => CsvFile::isWellFormed($file)),
            'pdf' => self::validateFile($file, 'PDF-Datei', static fn (): bool => PdfFile::isValid($file)),
            'xml' => self::validateFile($file, 'XML-Datei', static fn (): bool => XmlFile::isWellFormed($file)),
            'tif', 'tiff' => self::convertTiffFile($file),
            'zip' => self::extractZipFile($file),
            default => self::rejectUnknownFileType($file, $fileType),
        };
    }

    /**
     * @param Closure(): bool $isValid
     */
    private static function validateFile(string $file, string $label, Closure $isValid): bool {
        try {
            if (!$isValid()) {
                throw new Exception("Fehlerhafte $label: $file");
            }
            self::logInfo("$label $file erfolgreich validiert.");
        } catch (Throwable $e) {
            self::logException($e);
        }

        return true;
    }

    private static function convertTiffFile(string $file): bool {
        try {
            if (File::mimeType($file) !== 'image/tiff') {
                self::logWarning("$file ist ungültig, versuche zu reparieren.");
                TifFile::repair($file);
            }
            TifFile::convertToPdf($file);
            self::logInfo("TIFF-Datei $file wurde erfolgreich in PDF umgewandelt.");
        } catch (Throwable $e) {
            self::logException($e);
        }

        return true;
    }

    private static function extractZipFile(string $file): bool {
        try {
            if (!ZipFile::isValid($file)) {
                throw new Exception("Fehlerhaftes ZIP-Archiv: $file");
            }
            self::logInfo("ZIP-Archiv $file erfolgreich validiert.");

            $config = Config::getInstance();
            ZipFile::extract(
                $file,
                pathinfo($file, PATHINFO_DIRNAME),
                maxEntries: $config->getZipMaxEntries(),
                maxBytes: $config->getZipMaxBytes(),
                maxRatio: $config->getZipMaxRatio(),
            );
        } catch (Throwable $e) {
            self::logException($e);
        }

        return true;
    }

    private static function rejectUnknownFileType(string $file, string $fileType): bool {
        self::logWarning("Unbekannter Dateityp: $fileType für Datei $file");
        return false;
    }
}

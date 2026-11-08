#!/usr/bin/php
<?php
/*
 * Created on   : Fri Oct 18 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevFileDispatcher.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Factories\LoggerFactory;
use App\Helper\FileDispatcher;
use CommonToolkit\Helper\FileSystem\File;
use Throwable;

/*
 * Aufruf:
 *   DatevFileDispatcher.php <datei>                                   verarbeitet eine Datei
 *   DatevFileDispatcher.php --list <verzeichnis> [<mindestalter_s>]   listet unverarbeitete Dateien (NUL-getrennt)
 *
 * Exit-Codes: 0 = ok bzw. nichts zu tun, 1 = Fehler, 99 = fehlerhafter Aufruf
 */

$self = basename($argv[0]);

/** Fehler gehen zusätzlich auf STDERR, damit sie unabhängig vom konfigurierten Logger im Journal/Syslog landen. */
$fail = static function (string $message, int $exitCode = 1) use ($self): never {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] error: $self -> $message\n");
    exit($exitCode);
};

if ($argc < 2) {
    $fail("Fehlerhafter Aufruf. Verwendung: $self <datei_mit_vollstaendigem_pfad> | --list <verzeichnis> [<mindestalter_sekunden>]", 99);
}

if ($argv[1] === '--list') {
    if ($argc < 3) {
        $fail("Fehlerhafter Aufruf. Verwendung: $self --list <verzeichnis> [<mindestalter_sekunden>]", 99);
    }

    try {
        foreach (FileDispatcher::findPendingFiles($argv[2], (int) ($argv[3] ?? 0)) as $pendingFile) {
            echo $pendingFile, "\0";
        }
    } catch (Throwable $e) {
        $fail("Das Verzeichnis {$argv[2]} konnte nicht durchsucht werden: " . $e->getMessage());
    }
    exit(0);
}

$filename = $argv[1];

if (FileDispatcher::isIgnoredFile($filename)) {
    exit(0);
}

try {
    $logger = LoggerFactory::getLogger();
} catch (Throwable $e) {
    $fail("Die Konfiguration konnte nicht geladen werden: " . $e->getMessage());
}

$logger->info("$self -> Verarbeitung der Datei $filename begonnen.");

try {
    if (!File::isReady($filename)) {
        $logger->warning("$self -> Die Datei $filename ist nicht bereit. Warte maximal 30 Sekunden.");
        if (!File::wait4Ready($filename)) {
            $logger->error("$self -> Die Datei $filename konnte nicht geöffnet werden.");
            $fail("Die Datei $filename konnte nicht geöffnet werden.");
        }
    }

    FileDispatcher::processFile($filename);
} catch (Throwable $e) {
    $logger->error("$self -> Die Verarbeitung der Datei $filename wurde vorzeitig abgebrochen: " . $e->getMessage());
    $fail("Die Verarbeitung der Datei $filename wurde vorzeitig abgebrochen: " . $e->getMessage());
}

$logger->info("$self -> Verarbeitung der Datei $filename abgeschlossen.");
exit(0);

<?php
/*
 * Created on   : Fri Oct 18 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevFileDispatcher.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Helper\FileDispatcher;

$self = $argv[0];
$filename = null;

if ($argc < 2) {
    echo "Fehlerhafter Aufruf \n";
    echo $self . " Datei_mit_vollstaendigem_Pfad\n";
    exit(99);
} else {
    $filename = $argv[1];
}

if (strpos($filename, "log.txt")) die(0);
if (strpos($filename, "Arbeitnehmer online.docx")) die(0);

echo "[" . date('Y-m-d H:i:s') . "] info: $self -> Verarbeitung der Datei $filename begonnen...\n";

try {
    FileDispatcher::processFile($filename);
} catch (\Exception $e) {
    echo "\n\n[" . date('Y-m-d H:i:s') . "] error: $self -> Die Verabeitung der Datei $filename wurde vorzeitig abgebgrochen: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n\n[" . date('Y-m-d H:i:s') . "] info: $self -> Verarbeitung der Datei $filename abgeschlossen.\n";
exit(0);
<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https:\/\/schuppelius.org
 * Filename     : DmsMapper.php
 * License      : MIT License
 * License Uri  : https:\/\/opensource.org\/license\/mit
 */

namespace App\Helper;

class InternalStoreMapper {
    private static array $datevDMSMapping = [
        "01 Stammakte Einkommensbesch." => "04 Sonstiges/Einkommensbescheinigungen",
        "01 Stammakte Gebührenabrechnungen" => "04 Sonstiges/Sonstiges",
        "01 Stammakte Prüfungen" => "04 Sonstiges/Betriebsprüfungen",
        "01 Stammakte Sonstiges" => "04 Sonstiges/Sonstiges",
        "01 Stammakte Stammdaten" => "04 Sonstiges/Stammdaten",
        "01 Stammakte Verträge" => "04 Sonstiges/Verträge",
        "02 Steuern Dauerakte" => "03 Erklärungen, Abschlüsse, Bescheide",
        "02 Steuern ESt - KSt - Festst." => "03 Erklärungen, Abschlüsse, Bescheide",
        "02 Steuern ESt - KSt - Festst. Belege" => "03 Erklärungen, Abschlüsse, Bescheide",
        "02 Steuern GewSt" => "03 Erklärungen, Abschlüsse, Bescheide",
        "02 Steuern Steuervorausschau" => "04 Sonstiges/Sonstiges",
        "02 Steuern USt" => "03 Erklärungen, Abschlüsse, Bescheide",
        "03 Jahresabschluss Dauerakte" => "03 Erklärungen, Abschlüsse, Bescheide",
        "03 Jahresabschluss Jahresabschluss" => "03 Erklärungen, Abschlüsse, Bescheide",
        "04 FIBU Arbeitspapiere FIBU" => "01 Finanzbuchhaltung",
        "04 FIBU Auswertungen FIBU" => "01 Finanzbuchhaltung",
        "04 FIBU Finanzamt lfd." => "01 Finanzbuchhaltung/%s/FA Mahnungen, Umbuchung etc",
        "05 Lohn Diverses" => "02 Entgeltabrechnung",
        "05 Lohn Gehaltsabrechnungen" => "02 Entgeltabrechnung",
        "05 Lohn Sozialversicherung" => "02 Entgeltabrechnung",
        "05 Lohn Finanzamt" => "02 Entgeltabrechnung",
        "05 Lohn Mandantenunterlagen" => "02 Entgeltabrechnung",
        "Belege zu buchen" => "04 Sonstiges/Belege",
        "Belege gebucht" => "04 Sonstiges/Belege",
    ];

    private static array $perYear = [
        "03 Erklärungen, Abschlüsse, Bescheide",
    ];

    private static array $perPeriod = [
        "01 Finanzbuchhaltung",
        "02 Entgeltabrechnung",
    ];

    public static function getInternalStorePath(string $datevDMSCategory, ?string $parameter = null): ?string {
        if (array_key_exists($datevDMSCategory, self::$datevDMSMapping)) {
            $path = self::$datevDMSMapping[$datevDMSCategory];
            if ($parameter !== null && strpos($path, '%s') !== false) {
                return sprintf($path, $parameter);
            }
            return $path;
        }
        return null;
    }

    public static function requiresYear(string $internalPath): bool {
        foreach (self::$perYear as $pattern) {
            if (strpos($internalPath, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function requiresPeriod(string $internalPath): bool {
        foreach (self::$perPeriod as $pattern) {
            if (strpos($internalPath, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}

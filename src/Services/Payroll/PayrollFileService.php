<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollService.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Services\Payroll;

use APIToolkit\Enums\Month;
use APIToolkit\Logger\ConsoleLoggerFactory;
use Psr\Log\LoggerInterface;

class PayrollFileService {
    private LoggerInterface $logger;

    public function __construct() {
        $this->logger = ConsoleLoggerFactory::getLogger();
    }

    /**
     * Verarbeitet eine Payroll-Datei und bestimmt die Zielpfade basierend auf dem Dateinamen.
     *
     * @param string $filename
     * @param string &$dest_filename
     * @param string &$tenant
     * @param string &$month
     * @param string &$year
     * @return bool
     */
    public function processPayrollFile(string $filename, string &$dest_filename, string &$tenant, string &$month, string &$year): bool {
        $matches = [];

        $this->logger->info("Überprüfung, ob die Datei eine Payroll-Datei ist: {$filename}");

        // Muster: 50050_06_2023_Brutto_Netto_00007_F0A.pdf
        if (preg_match('/([0-9]{5})_([0-9]{2})_([0-9]{4})_Brutto_Netto_.+\.pdf$/', $filename, $matches)) {
            $tenant = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Format the month using the Month enum
            $monthFormatted = Month::from((int)$month)->getName('de');
            $month .= " " . $monthFormatted;

            $dest_filename = $matches[4];

            // L+G Mandantenliste holen und Mitarbeiterdaten verarbeiten
            if ($this->getEmployeeDataForTenant($tenant, $matches[4], $matches[5], $dest_filename)) {
                return true;
            }
        }

        // Weitere Datei-Muster wie SEPA oder Buchungsbelege
        return $this->processSepaFile($filename, $tenant, $month, $year);
    }

    /**
     * Holt die Mitarbeiterdaten eines Mandanten und bestimmt den Dateinamen.
     *
     * @param string $tenant
     * @param string $docType
     * @param string $employeeId
     * @param string &$dest_filename
     * @return bool
     */
    private function getEmployeeDataForTenant(string $tenant, string $docType, string $employeeId, string &$dest_filename): bool {
        $client_list = datev_api_request(BASE_URL_HR . "clients?reference-date=" . date("Y-m-d"));
        $client_id = $this->findClientId($client_list, $tenant);

        if (!$client_id) {
            $this->logger->error("Mandant {$tenant} nicht in HR-API gefunden.");
            return false;
        }

        $employee_name = "";
        $employee_list = datev_api_request(BASE_URL_HR . "clients/{$client_id}/employees?reference-date=" . date("Y-m-d"));

        foreach ($employee_list as $employee) {
            if ($employee['id'] == $employeeId) {
                $employee_name = $employee['surname'] . "_" . $employee['first_name'];
                $dest_filename = "{$docType}_{$employeeId}_{$employee_name}.pdf";
                return true;
            }
        }

        $this->logger->error("Mitarbeiter {$employeeId} für Mandant {$tenant} nicht gefunden.");
        return false;
    }

    /**
     * Bestimmt die Client-ID für einen Mandanten.
     *
     * @param array $client_list
     * @param string $tenant
     * @return string|null
     */
    private function findClientId(array $client_list, string $tenant): ?string {
        foreach ($client_list as $client) {
            if ($client['number'] == $tenant) {
                return $client['id'];
            }
        }
        return null;
    }

    /**
     * Verarbeitet SEPA-Dateien und Buchungsbelege.
     *
     * @param string $filename
     * @param string &$tenant
     * @param string &$month
     * @param string &$year
     * @return bool
     */
    private function processSepaFile(string $filename, string &$tenant, string &$month, string &$year): bool {
        $matches = [];

        // Muster für SEPA-Dateien
        if (preg_match('/SEPA-([0-9]{5})-([0-9]{4})_([0-9]{2})-.+\.pdf$/', $filename, $matches)) {
            $tenant = $matches[1];
            $year = $matches[2];
            $month = $matches[3];

            // Format the month using the Month enum
            $monthFormatted = Month::from((int)$month)->getName('de');
            $month .= " " . $monthFormatted;

            return true;
        }

        // Weitere Muster wie Buchungsbelege können hier hinzugefügt werden.
        return false;
    }
}

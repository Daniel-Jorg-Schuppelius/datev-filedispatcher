<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollDestinationTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Services\Payroll;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use App\Config\Config;
use App\Contracts\Abstracts\FileServices\FileServiceAbstract;
use App\Factories\StorageFactory;
use App\Services\Payroll;
use FilesystemIterator;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use RuntimeException;

/**
 * Zieldateinamen und -ordner der Lohn-Services gegen einen gemockten API-Client.
 */
class PayrollDestinationTest extends TestCase {
    private const GUID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const MASTER_DATA_CLIENT = ['id' => self::GUID, 'name' => 'Musterfirma GmbH', 'number' => 20520];
    private const PAYROLL_CLIENT = ['id' => self::GUID, 'name' => 'Musterfirma GmbH', 'number' => 20520, 'consultant_number' => 12345];
    private const EMPLOYEES = [['id' => '00001', 'surname' => 'Muster', 'first_name' => 'Max']];

    /**
     * @return array<string, array{class-string<FileServiceAbstract>, string, string}>
     */
    public static function filenameProvider(): array {
        return [
            'Abrechnungsart im Namen' => [Payroll\SettlementOfGrossNetRemunerationsFileService::class, '20520_10_2024_Brutto_Netto_Bau_00001_AA0.pdf', 'Entgeltabrechnung-Bau_00001_Muster_Max.pdf'],
            'Fallback Entgeltabrechnung' => [Payroll\EmployeeFileService::class, '20520_10_2024_Brutto_Netto_00001_AA0.pdf', 'Entgeltabrechnung-00001_Muster_Max.pdf'],
            'Fallback SV-Meldung' => [Payroll\EmployeeFileService::class, '20520_10_2024_SV_Nachweis_(DEÜV)_00001_AA1.pdf', 'Sozialversicherungsmeldung-00001_Muster_Max.pdf'],
            'Fallback unbekannte Dokumentart' => [Payroll\EmployeeFileService::class, '20520_10_2024_Irgendwas_Neues_00001_AA1.pdf', 'Irgendwas_Neues-00001_Muster_Max.pdf'],
            'Lohnkonto' => [Payroll\PayrollAccountsFileService::class, '20520_10_2024_Lohnkonto_00001_AA0.pdf', 'Lohnkonto-00001_Muster_Max.pdf'],
            'Sachbezug' => [Payroll\EmployeeNonCashBenefitsFileService::class, '20520_10_2024_BerechSchema_Firmenwagen_00001_AA0.pdf', 'BerechSchema_Firmenwagen-00001_Muster_Max.pdf'],
            'Mitarbeiter vorne im Namen' => [Payroll\EmployeePayrollFileService::class, '20520_00001_Max_Muster_10_2024_Brutto_Netto_AA0.pdf', 'Entgeltabrechnung-00001_Muster_Max.pdf'],
            'mehrere Monate' => [Payroll\SettlementOfGrossNetRemunerations4SeveralMonthsFileService::class, '20520_01_2024_12_2024_Brutto_Netto_00001.pdf', 'Mehrmonatige_Entgeltabrechnung-00001_Muster_Max.pdf'],
            'fester Dokumenttyp' => [Payroll\PayrollJournalFileService::class, '20520_10_2024_Lohnjournal_AA0.pdf', 'Lohnjournal.pdf'],
            'andere Endung' => [Payroll\BookingBatchFileService::class, 'DTVF_000000_20520_LOHNBUCHUNGEN_LUG_202410_20241105_1234.csv', 'DTVF_Lohnbuchungen.csv'],
            'Endung aus dem Namen' => [Payroll\BookingBatchManualFileService::class, 'LUG_Buchungsbelege_000000_20520_202410.txt', 'Lohnbuchungsbelege.txt'],
            'SEPA' => [Payroll\SEPAPayrollFileService::class, 'SEPA-20520-2024_10-C01-Lohn_Gehalt.xml', 'SEPA-Lohn_Gehalt_2024_10.xml'],
            'SKUG' => [Payroll\SKUGFileService::class, '20520_10_2024_Leistungsantr_Saison_Kug_A01.pdf', 'SKUG-Antrag-Leistungsantr.pdf'],
            'Lohnarten' => [Payroll\EmployeesWageTypesFileService::class, '000000_20520_ABC GbR_10_2024.xlsm', 'Lohnarten-ABC GbR.xlsm'],
            'Originalname bleibt' => [Payroll\PersonnelCalendarFileService::class, '20520_10_2024_Personalkalender_Div_Mitarbeiter_AA0.pdf', '20520_10_2024_Personalkalender_Div_Mitarbeiter_AA0.pdf'],
        ];
    }

    /**
     * @param class-string<FileServiceAbstract> $serviceClass
     */
    #[DataProvider('filenameProvider')]
    public function test_destination_filename(string $serviceClass, string $filename, string $expected): void {
        $service = $this->createService($serviceClass, $filename);

        $this->assertSame($expected, (new ReflectionMethod($service, 'getDestinationFilename'))->invoke($service));
    }

    public function test_unknown_employee_is_reported(): void {
        $service = $this->createService(Payroll\PayrollAccountsFileService::class, '20520_10_2024_Lohnkonto_99999_AA0.pdf');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mitarbeiter nicht gefunden: 99999');
        (new ReflectionMethod($service, 'getDestinationFilename'))->invoke($service);
    }

    public function test_forced_yearly_storage_ignores_period_configuration(): void {
        $storeRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filedispatcher_store_' . bin2hex(random_bytes(4));
        // Jahresordner mit und ohne Vorjahresordner anlegen, je nach lokaler Konfiguration (maxYears).
        $previousYearsFolder = Config::getInstance()->getPreviousYearsFolderName4Internal() ?? 'Vorjahre';
        foreach (['2024', $previousYearsFolder . DIRECTORY_SEPARATOR . '2024'] as $yearFolder) {
            mkdir($storeRoot . DIRECTORY_SEPARATOR . '20520' . DIRECTORY_SEPARATOR . '02 Entgeltabrechnung' . DIRECTORY_SEPARATOR . $yearFolder, 0777, true);
        }

        try {
            StorageFactory::setInternalStorePath($storeRoot . DIRECTORY_SEPARATOR . '{tenant}');
            $service = $this->createService(Payroll\SettlementOfGrossNetRemunerations4SeveralMonthsFileService::class, '20520_01_2024_12_2024_Brutto_Netto_00001.pdf');

            $folder = $service->getDestinationFolder();

            $this->assertNotNull($folder);
            $this->assertStringEndsWith(DIRECTORY_SEPARATOR . '2024', $folder);
            $this->assertStringContainsString('02 Entgeltabrechnung', $folder);
        } finally {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storeRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }
            rmdir($storeRoot);
        }
    }

    /**
     * @template T of FileServiceAbstract
     * @param class-string<T> $serviceClass
     * @return T
     */
    private function createService(string $serviceClass, string $filename): FileServiceAbstract {
        $client = $this->createMock(ApiClientInterface::class);
        $client->method('get')->willReturnCallback(function (string $uri): ResponseInterface {
            $path = (string) parse_url($uri, PHP_URL_PATH);

            return match (true) {
                $path === '/datev/api/master-data/v1/clients' => self::json([self::MASTER_DATA_CLIENT]),
                $path === '/datev/api/hr/v3/clients' => self::json([self::PAYROLL_CLIENT]),
                str_starts_with($path, '/datev/api/hr/v3/clients/') => self::json(self::PAYROLL_CLIENT + ['employees' => self::EMPLOYEES]),
                default => throw new LogicException("Unerwarteter Request: $uri"),
            };
        });

        return new $serviceClass(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename, $client, new NullLogger);
    }

    /**
     * @param array<mixed> $data
     */
    private static function json(array $data): ResponseInterface {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }
}

<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ServiceRoutingTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Services;

use App\Helper\FileDispatcher;
use App\Services\{DMSBasicFileService, Payroll};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Prüft, welcher FileService eine Datei bekommt. Die Patterns überschneiden sich teilweise
 * (z. B. der generische EmployeeFileService mit spezifischen Mitarbeiter-Auswertungen),
 * entscheidend ist daher die Reihenfolge im FileDispatcher, nicht das einzelne Pattern.
 * Kommt kein API-Zugriff aus, weil nur Dateinamen geprüft werden.
 */
class ServiceRoutingTest extends TestCase {
    /**
     * @return array<string, array{string, class-string}>
     */
    public static function routingProvider(): array {
        $cases = [
            ['219628 - Lohn Mandantenunterlagen.pdf', DMSBasicFileService::class],
            ['000000 - ABC Testdokument - 2022_1.pdf', DMSBasicFileService::class],
            ['00000_10_2024_Meldeverlauf_A1_AA0.pdf', Payroll\A1ReportingHistoryFileService::class],
            ['DTVF_000000_00000_LOHNBUCHUNGEN_LUG_202305_20230605_1234.csv', Payroll\BookingBatchFileService::class],
            ['LUG_Buchungsbelege_000000_00000_202312.CSV', Payroll\BookingBatchManualFileService::class],
            ['LUG_Buchungsbelege_000000_00000_202401.txt', Payroll\BookingBatchManualFileService::class],
            ['00000_10_2024_Buchungsbeleg_Kontennachweis_pro_Mitarbeiter_Div_Mitarbeiter_AA0.pdf', Payroll\BookingReceiptAccountingsFileService::class],
            ['00000_10_2024_Buchungsbeleg_AA0.pdf', Payroll\BookingReceiptFileService::class],
            ['00000_10_2024_Barauszahlungswerte_AA0.pdf', Payroll\CashPaymentValuesFileService::class],
            ['00000_10_2024_DÜ_Protok_LSt_Anm_AA0.pdf', Payroll\DataProtocolIncomeTaxDeclarationFileService::class],
            ['00000_10_2024_DÜ_Prot_Betriebsdaten_AA0.pdf', Payroll\DataProtocolOperatingDataFileService::class],
            ['00000_10_2024_DÜ_Prot_Lohnnachweis_Unfallversicherung_AA0.pdf', Payroll\DataProtocolPaymentsContributionInvoiceUVFileService::class],
            ['00000_10_2024_DÜ_Protok_Überweisungen_AA0.pdf', Payroll\DataProtocolPaymentsFileService::class],
            ['00000_10_2024_DÜ_Protok_Beitragsn_AA0.pdf', Payroll\DataProtocolProofOfAmountFileService::class],
            ['00000_10_2024_Dialoginhalt_Sammel_AW_AA0.pdf', Payroll\EAUStatusOverviewFileService::class],
            ['00000_10_2024_Brutto_Netto_01000_AA0.pdf', Payroll\EmployeeFileService::class],
            ['00000_10_2024_SV_Nachweis_(DEÜV)_00004_AA1.pdf', Payroll\EmployeeFileService::class],
            ['00000_10_2024_DÜ_Prot_LSt_Bescheinig_00004_AA1.pdf', Payroll\EmployeeFileService::class],
            ['Arbeitnehmer online - Einladungsstatus - 000000 - 00000 - Regina Wegner.csv', Payroll\EmployeeInvitationFileService::class],
            ['00000_10_2024_BerechSchema_Firmenwagen_00001_AA0.pdf', Payroll\EmployeeNonCashBenefitsFileService::class],
            ['00000_00000_Client_Client_00_0000_Brutto_Netto_AA0.pdf', Payroll\EmployeePayrollFileService::class],
            ['000000_00000_ABC GbR_03_2024.xlsm', Payroll\EmployeesWageTypesFileService::class],
            ['00000_11_2024_Übern_Prot_Rückm_Verf_AA0.pdf', Payroll\HandoverProtocolFeedbackFileService::class],
            ['00000_10_2024_KK_SV_Werte___gesamt_v_AA0.pdf', Payroll\HealthInsuranceSVValuesFileService::class],
            ['00000_10_2024_KK_SV_Werte___gesamt_AA0.pdf', Payroll\HealthInsuranceSVValuesFileService::class],
            ['00000_10_2024_KK_SV_Werte___Summen_v_AA0.pdf', Payroll\HealthInsuranceSVValuesSumFileService::class],
            ['00000_10_2024_KK_SV_Werte___Summen_AA0.pdf', Payroll\HealthInsuranceSVValuesSumFileService::class],
            ['00000_10_2024_Einzelaufstellung_Unfallvers_(monatlich)_AA0.pdf', Payroll\IndividualListOfAccidentInsuranceMonthFileService::class],
            ['00000_10_2024_Einzelaufstellung_Unfallvers_(jährlich)_AA0.pdf', Payroll\IndividualListOfAccidentInsuranceYearFileService::class],
            ['00000_10_2024_Stammdatenänd_Prot_AA0.pdf', Payroll\MasterDataChangeProtocolFileService::class],
            ['00000_10_2024_Überzahlungen_Werte_AA0.pdf', Payroll\OverpaymentValuesFileService::class],
            ['00000_10_2024_Übersicht_DÜ_Erstatt_AAG_AA0.pdf', Payroll\OverviewOfReimbursementsAAGFileService::class],
            ['00000_10_2024_Zahlungsverkehr_Summen.pdf', Payroll\PaymentOrderFileService::class],
            ['00000_10_2024_Übersicht_Zahlungen_AA0.pdf', Payroll\PaymentOverviewsFileService::class],
            ['00000_10_2024_Zahlungsverkehr_Summen_AA0.pdf', Payroll\PaymentTransactionAmountsFileService::class],
            ['00000_10_2024_Überweisungsprotokoll_AA0.pdf', Payroll\PaymentTransferProtocolFileService::class],
            ['00000_10_2024_Lohnkonto_00001_AA0.pdf', Payroll\PayrollAccountsFileService::class],
            ['00000_10_2024_Lohnjournal_Jahresw_AA0.pdf', Payroll\PayrollJournalAnnualValuesFileService::class],
            ['00000_10_2024_Lohnjournal_AA0.pdf', Payroll\PayrollJournalFileService::class],
            ['00000_10_2024_Personalkalender_Div_Mitarbeiter_AA0.pdf', Payroll\PersonnelCalendarFileService::class],
            ['00000_11_2024_Personalreport_10_2024_AA0.pdf', Payroll\PersonnelReportFileService::class],
            ['SEPA-00000-2023_05-C01-Lohn_Gehalt.xml', Payroll\SEPAPayrollFileService::class],
            ['00000_10_2024_Begleitpapiere_Datei_AA0.pdf', Payroll\SEPAVotingListFileService::class],
            ['00000_00_0000_Leistungsantr_Saison_Kug_A01.pdf', Payroll\SKUGFileService::class],
            ['00000_00_0000_Anlage_LA_Saison_Kug_A01.pdf', Payroll\SKUGFileService::class],
            ['00000_10_2024_Brutto_Netto_Probe_Div_Mitarbeiter_AA0.pdf', Payroll\SettlementOfGrossNetEarningsEmployeesFileService::class],
            ['00000_10_2024_10_2024_Brutto_Netto_00001.pdf', Payroll\SettlementOfGrossNetRemunerations4SeveralMonthsFileService::class],
            ['00000_10_2024_Brutto_Netto_Bau_00001_AA0.pdf', Payroll\SettlementOfGrossNetRemunerationsFileService::class],
            ['00000_10_2024_Div_Auswertungen_Div_Mitarbeiter_AA0.pdf', Payroll\VariousEvaluationForEmployeesFileService::class],
            ['00000_08_2026_Div_Auswertungen_L02.pdf', Payroll\VariousEvaluationsFileService::class],
        ];

        $named = [];
        foreach ($cases as [$filename, $expected]) {
            $named[$filename] = [$filename, $expected];
        }
        return $named;
    }

    /**
     * @param class-string $expected
     */
    #[DataProvider('routingProvider')]
    public function test_file_is_routed_to_expected_service(string $filename, string $expected): void {
        $this->assertSame($expected, FileDispatcher::resolveServiceClass('/eingang/' . $filename));
    }

    public function test_every_service_is_covered_by_routing_cases(): void {
        FileDispatcher::resolveServiceClass('/eingang/initialisieren.pdf');
        /** @var list<class-string> $services */
        $services = (new ReflectionProperty(FileDispatcher::class, 'services'))->getValue();

        $covered = array_unique(array_column(self::routingProvider(), 1));
        $missing = array_diff($services, $covered);

        $this->assertNotEmpty($services, 'Es wurden keine Services geladen.');
        $this->assertSame([], array_values($missing), 'Für diese Services fehlt ein Routing-Testfall: ' . implode(', ', $missing));
    }

    public function test_generic_employee_service_is_checked_last(): void {
        FileDispatcher::resolveServiceClass('/eingang/initialisieren.pdf');
        /** @var list<class-string> $services */
        $services = (new ReflectionProperty(FileDispatcher::class, 'services'))->getValue();

        $this->assertSame(Payroll\EmployeeFileService::class, end($services));
    }

    public function test_unknown_file_has_no_service(): void {
        $this->assertNull(FileDispatcher::resolveServiceClass('/eingang/irgendwas.docx'));
    }

    public function test_sepa_pattern_does_not_exhaust_backtracking(): void {
        // Das frühere Pattern "(.+)+" lief bei nicht passenden SEPA-Namen in das Backtrack-Limit.
        $filename = 'SEPA-12345-2023_05-C01-' . str_repeat('a', 200) . '.xmlx';

        $this->assertFalse(Payroll\SEPAPayrollFileService::matchesPattern($filename));
        $this->assertSame(PREG_NO_ERROR, preg_last_error());
    }
}

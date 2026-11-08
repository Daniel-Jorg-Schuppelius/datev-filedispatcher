<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollClientResolutionTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Services\Payroll;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use APIToolkit\Exceptions\InternalServerErrorException;
use App\Services\Payroll\SettlementOfGrossNetRemunerations4SeveralMonthsFileService;
use Datev\Entities\Payroll\Clients\Client as PayrollClient;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;

/**
 * Ermittlung von Client und Client (Payroll) gegen einen gemockten API-Client.
 */
class PayrollClientResolutionTest extends TestCase {
    private const GUID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const PAST_FILE = '/tmp/20520_01_2024_12_2024_Brutto_Netto_12006.pdf';

    private const MASTER_DATA_CLIENT = ['id' => self::GUID, 'name' => 'Musterfirma GmbH', 'number' => 20520];
    private const PAYROLL_CLIENT = ['id' => self::GUID, 'name' => 'Musterfirma GmbH', 'number' => 20520, 'consultant_number' => 12345];

    /** @var list<string> Payroll-Requests als "search:<Stichtag>" bzw. "get:<Stichtag>" */
    private array $payrollRequests = [];

    public function test_past_period_uses_end_of_period_as_reference_date(): void {
        $service = $this->createService(self::PAST_FILE, fn () => self::json([self::PAYROLL_CLIENT]));

        $this->assertSame(['search:2024-12-31', 'get:2024-12-31'], $this->payrollRequests);
        $this->assertInstanceOf(PayrollClient::class, $this->getPayrollClient($service));
        $this->assertSame(20520, $service->getClient()?->getNumber());
    }

    public function test_past_period_falls_back_to_today_on_api_error(): void {
        $today = date('Y-m-d');
        $service = $this->createService(self::PAST_FILE, function (string $referenceDate) {
            if ($referenceDate === '2024-12-31') {
                throw new InternalServerErrorException('Internal Server Error', 500, new Response(500), null, new NullLogger);
            }
            return self::json([self::PAYROLL_CLIENT]);
        });

        $this->assertSame(['search:2024-12-31', "search:$today", "get:$today"], $this->payrollRequests);
        $this->assertInstanceOf(PayrollClient::class, $this->getPayrollClient($service));
    }

    public function test_past_period_falls_back_to_today_when_client_is_missing(): void {
        $today = date('Y-m-d');
        $this->createService(self::PAST_FILE, fn (string $referenceDate) => $referenceDate === '2024-12-31' ? self::json([]) : self::json([self::PAYROLL_CLIENT]));

        $this->assertSame(['search:2024-12-31', "search:$today", "get:$today"], $this->payrollRequests);
    }

    public function test_current_period_api_error_is_thrown_without_repeated_request(): void {
        $year = date('Y');
        $file = sprintf('/tmp/20520_01_%1$s_%2$s_%1$s_Brutto_Netto_12006.pdf', $year, date('m'));

        try {
            $this->createService($file, fn () => throw new InternalServerErrorException('Internal Server Error', 500, new Response(500), null, new NullLogger));
            $this->fail('InternalServerErrorException erwartet');
        } catch (InternalServerErrorException) {
            $this->assertSame(['search:' . date('Y-m-d')], $this->payrollRequests);
        }
    }

    public function test_missing_payroll_client_is_searched_once_per_reference_date(): void {
        try {
            $otherClient = ['number' => 99999] + self::PAYROLL_CLIENT;
            $this->createService(self::PAST_FILE, fn () => self::json([$otherClient]));
            $this->fail('RuntimeException erwartet');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Client (Payroll) konnte nicht gefunden werden: 20520', $e->getMessage());
            $this->assertSame(['search:2024-12-31', 'search:' . date('Y-m-d')], $this->payrollRequests);
        }
    }

    public function test_client_is_derived_from_payroll_client(): void {
        // Suche per Mandantennummer liefert nichts, der Abruf über die GUID schon.
        $service = $this->createService(
            self::PAST_FILE,
            fn () => self::json([self::PAYROLL_CLIENT]),
            fn (array $query) => isset($query['filter']) ? self::json([]) : self::json([self::MASTER_DATA_CLIENT])
        );

        $this->assertSame(20520, $service->getClient()?->getNumber());
        $this->assertInstanceOf(PayrollClient::class, $this->getPayrollClient($service));
    }

    /**
     * @param callable(string): ResponseInterface $payrollSearch erhält den Stichtag
     * @param (callable(array<string, mixed>): ResponseInterface)|null $masterDataSearch erhält die Query-Parameter
     */
    private function createService(string $file, callable $payrollSearch, ?callable $masterDataSearch = null): SettlementOfGrossNetRemunerations4SeveralMonthsFileService {
        $masterDataSearch ??= fn () => self::json([self::MASTER_DATA_CLIENT]);

        $client = $this->createMock(ApiClientInterface::class);
        $client->method('get')->willReturnCallback(function (string $uri) use ($payrollSearch, $masterDataSearch): ResponseInterface {
            $path = (string) parse_url($uri, PHP_URL_PATH);
            parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
            $referenceDate = (string) ($query['reference-date'] ?? '');

            if ($path === '/datev/api/master-data/v1/clients') {
                return $masterDataSearch($query);
            }
            if ($path === '/datev/api/hr/v3/clients') {
                $this->payrollRequests[] = "search:$referenceDate";
                return $payrollSearch($referenceDate);
            }
            if (str_starts_with($path, '/datev/api/hr/v3/clients/')) {
                $this->payrollRequests[] = "get:$referenceDate";
                return self::json(self::PAYROLL_CLIENT);
            }

            throw new LogicException("Unerwarteter Request: $uri");
        });

        return new SettlementOfGrossNetRemunerations4SeveralMonthsFileService($file, $client, new NullLogger);
    }

    private function getPayrollClient(object $service): mixed {
        return (new ReflectionProperty($service, 'payrollClient'))->getValue($service);
    }

    /**
     * @param array<mixed> $data
     */
    private static function json(array $data): ResponseInterface {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }
}

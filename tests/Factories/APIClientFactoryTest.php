<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : APIClientFactoryTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Factories;

use APIToolkit\Contracts\Interfaces\API\ApiClientInterface;
use App\Factories\APIClientFactory;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Client mit CA-Unterstützung, wie ihn php-api-toolkit ab setCaBundle() liefert.
 */
interface CaBundleAwareClient extends ApiClientInterface {
    public function setCaBundle(?string $path): void;
}

class APIClientFactoryTest extends TestCase {
    public function test_ca_file_is_passed_to_the_client(): void {
        $client = $this->createMock(CaBundleAwareClient::class);
        $client->expects($this->once())->method('setCaBundle')->with('/etc/filedispatcher/datevconnect-ca.pem');

        $this->applyCaFile($client, '/etc/filedispatcher/datevconnect-ca.pem');
    }

    public function test_ca_file_without_toolkit_support_is_reported(): void {
        $client = $this->createMock(ApiClientInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('setCaBundle()');
        $this->applyCaFile($client, '/etc/filedispatcher/datevconnect-ca.pem');
    }

    private function applyCaFile(ApiClientInterface $client, string $caFile): void {
        (new ReflectionMethod(APIClientFactory::class, 'applyCaFile'))->invoke(null, $client, $caFile);
    }
}

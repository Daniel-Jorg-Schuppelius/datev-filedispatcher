<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : InternalStoreMapperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use PHPUnit\Framework\TestCase;
use App\Helper\InternalStoreMapper;
use Datev\Entities\ClientMasterData\Clients\Client;
use App\Factories\StorageFactory;
use Datev\Entities\DocumentManagement\Documents\Document;
use Datev\Entities\DocumentManagement\Documents\Folders\DocumentFolder;
use Datev\Entities\DocumentManagement\Registers\Register;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InternalStoreMapperTest extends TestCase {

    private string $tempDir;
    private string $internalStorePath;

    protected function setUp(): void {
        parent::setUp();
        $this->internalStorePath = sys_get_temp_dir() . '/internal_store_test/{tenant}';
        $this->tempDir = str_replace("{tenant}", "12345", $this->internalStorePath);

        if (!is_dir(dirname($this->tempDir))) {
            mkdir(dirname($this->tempDir));
        }
        mkdir($this->tempDir);

        StorageFactory::setInternalStorePath($this->internalStorePath);

        $categories = [
            "04 Sonstiges/Einkommensbescheinigungen",
            "01 Finanzbuchhaltung/2024/FA Mahnungen, Umbuchung etc"
        ];

        foreach ($categories as $category) {
            $fullPath = $this->tempDir . '/' . $category;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0777, true);
            }
        }
    }

    protected function tearDown(): void {
        if (is_dir($this->tempDir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testGetInternalStorePathWithoutParameter() {
        $client = $this->createMock(Client::class);
        $document = $this->createMock(Document::class);
        $register = $this->createMock(Register::class);
        $folder = $this->createMock(DocumentFolder::class);

        $client->method('getNumber')->willReturn(12345);
        $document->method('getRegister')->willReturn($register);
        $document->method('getFolder')->willReturn($folder);

        $register->method('getName')->willReturn("Einkommensbesch.");
        $folder->method('getName')->willReturn("01 Stammakte");

        $expectedPath = str_replace("/", DIRECTORY_SEPARATOR, "04 Sonstiges/Einkommensbescheinigungen");

        $actualPath = InternalStoreMapper::getInternalStorePath4Document($client, $document);

        $this->assertNotNull($actualPath, "Der Pfad sollte nicht null sein.");
        $this->assertStringContainsString($expectedPath, $actualPath, "Der erwartete Pfad ist nicht korrekt.");
    }

    public function testGetInternalStorePathWithParameter() {
        $client = $this->createMock(Client::class);
        $document = $this->createMock(Document::class);
        $register = $this->createMock(Register::class);
        $folder = $this->createMock(DocumentFolder::class);

        $client->method('getNumber')->willReturn(12345);
        $document->method('getRegister')->willReturn($register);
        $document->method('getFolder')->willReturn($folder);

        $register->method('getName')->willReturn("Finanzamt lfd.");
        $folder->method('getName')->willReturn("04 FIBU");

        $parameter = "2024";
        $expectedPath = str_replace("/", DIRECTORY_SEPARATOR, "01 Finanzbuchhaltung/2024/FA Mahnungen, Umbuchung etc");

        $actualPath = InternalStoreMapper::getInternalStorePath4Document($client, $document, $parameter);

        $this->assertNotNull($actualPath, "Der Pfad sollte nicht null sein.");
        $this->assertStringContainsString($expectedPath, $actualPath, "Der erwartete Pfad ist nicht korrekt.");
    }

    public function testGetInternalStorePathInvalidCategory() {
        $client = $this->createMock(Client::class);
        $document = $this->createMock(Document::class);
        $register = $this->createMock(Register::class);
        $folder = $this->createMock(DocumentFolder::class);

        $client->method('getNumber')->willReturn(12345);
        $document->method('getRegister')->willReturn($register);
        $document->method('getFolder')->willReturn($folder);

        $register->method('getName')->willReturn("Register");
        $folder->method('getName')->willReturn("Folder");

        $actualPath = InternalStoreMapper::getInternalStorePath4Document($client, $document);

        $this->assertNull($actualPath, "Der Pfad sollte null sein, da das Register und Folder Objekt im Dokument ungültig ist.");
    }

    public function testRequiresYearTrue() {
        $internalPath = "03 Erklärungen, Abschlüsse, Bescheide";
        $this->assertTrue(InternalStoreMapper::requiresYear($internalPath), "Es wird erwartet, dass das Jahr erforderlich ist.");
    }

    public function testRequiresYearFalse() {
        $internalPath = "01 Finanzbuchhaltung";
        $this->assertFalse(InternalStoreMapper::requiresYear($internalPath), "Es wird erwartet, dass das Jahr nicht erforderlich ist.");
    }

    public function testRequiresPeriodTrue() {
        $internalPath = "01 Finanzbuchhaltung";
        $this->assertTrue(InternalStoreMapper::requiresPeriod($internalPath), "Es wird erwartet, dass der Zeitraum erforderlich ist.");
    }

    public function testRequiresPeriodFalse() {
        $internalPath = "03 Erklärungen, Abschlüsse, Bescheide";
        $this->assertFalse(InternalStoreMapper::requiresPeriod($internalPath), "Es wird erwartet, dass der Zeitraum nicht erforderlich ist.");
    }
}

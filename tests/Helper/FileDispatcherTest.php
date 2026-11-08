<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileDispatcherTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use App\Helper\FileDispatcher;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileDispatcherTest extends TestCase {
    private string $tempDir;

    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filedispatcher_test_' . bin2hex(random_bytes(4));
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'sub', 0777, true);
    }

    protected function tearDown(): void {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tempDir);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function ignoredFileProvider(): array {
        return [
            'Logdatei' => ['/eingang/log.txt', true],
            'rotierte Logdatei' => ['/eingang/log.txt.20241021_132856', true],
            'Anleitung Arbeitnehmer' => ['/eingang/Arbeitnehmer online.docx', true],
            'Anleitung Arbeitgeber' => ['/eingang/AG Arbeitnehmer online.docx', true],
            'Lockfile' => ['/eingang/235310 - Bescheid.tif.lock', true],
            'Logdatei am Pfadanfang' => ['log.txt', true],
            'Datei mit log.txt im Namen' => ['/eingang/changelog.txt.pdf', false],
            'normale Datei' => ['/eingang/219628 - Lohn Mandantenunterlagen.pdf', false],
            'Ordner namens log.txt' => ['/eingang/log.txt/datei.pdf', false],
        ];
    }

    #[DataProvider('ignoredFileProvider')]
    public function test_is_ignored_file(string $file, bool $expected): void {
        $this->assertSame($expected, FileDispatcher::isIgnoredFile($file));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function folderProvider(): array {
        return [
            'Datei im Ordner' => ['/data/abc/datei.pdf', '/data/abc', true],
            'Datei in Unterordner' => ['/data/abc/x/datei.pdf', '/data/abc', true],
            'Ordner mit Schrägstrich am Ende' => ['/data/abc/datei.pdf', '/data/abc/', true],
            'Ordner selbst' => ['/data/abc', '/data/abc', true],
            'gleicher Präfix, anderer Ordner' => ['/data/abcdef/datei.pdf', '/data/abc', false],
            'Windows-Trenner' => ['C:\\data\\abc\\datei.pdf', 'C:/data/abc', true],
            'leerer Ordner' => ['/data/abc/datei.pdf', '', false],
        ];
    }

    #[DataProvider('folderProvider')]
    public function test_is_in_folder(string $file, string $folder, bool $expected): void {
        $this->assertSame($expected, FileDispatcher::isInFolder($file, $folder));
    }

    public function test_find_pending_files_skips_ignored_hidden_and_empty_files(): void {
        $this->createFile('219628 - Lohn Mandantenunterlagen.pdf');
        $this->createFile('sub' . DIRECTORY_SEPARATOR . '00000_10_2024_Lohnjournal_AA0.pdf');
        $this->createFile('log.txt');
        $this->createFile('Arbeitnehmer online.docx');
        $this->createFile('.versteckt.pdf');
        $this->createFile('OHNEENDUNG');
        $this->createFile('leer.pdf', '');
        $this->createFile('dokument.tif.lock');

        $expected = [
            $this->path('219628 - Lohn Mandantenunterlagen.pdf'),
            $this->path('sub' . DIRECTORY_SEPARATOR . '00000_10_2024_Lohnjournal_AA0.pdf'),
        ];
        sort($expected);

        $this->assertSame($expected, FileDispatcher::findPendingFiles($this->tempDir));
    }

    public function test_find_pending_files_respects_minimum_age(): void {
        $this->createFile('alt.pdf');
        touch($this->path('alt.pdf'), time() - 600);
        $this->createFile('neu.pdf');

        $this->assertSame([$this->path('alt.pdf')], FileDispatcher::findPendingFiles($this->tempDir, 60));
    }

    public function test_find_pending_files_in_missing_directory(): void {
        $this->assertSame([], FileDispatcher::findPendingFiles($this->tempDir . DIRECTORY_SEPARATOR . 'gibtsnicht'));
    }

    private function createFile(string $relativePath, string $content = 'inhalt'): void {
        file_put_contents($this->path($relativePath), $content);
    }

    private function path(string $relativePath): string {
        return (string) realpath($this->tempDir) . DIRECTORY_SEPARATOR . $relativePath;
    }
}

<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {
    private $testFile;

    protected function setUp(): void {
        $this->testFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($this->testFile, 'This is a test file.');
    }

    protected function tearDown(): void {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function test_mime_type() {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('text/plain', $mimeType);
    }

    public function test_mime_type_failure() {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertEmpty(File::mimeType($invalidFile));
    }

    public function test_file_exists_returns_true_for_existing_file() {
        $testFile = tempnam(sys_get_temp_dir(), 'test');
        $this->assertTrue(File::exists($testFile));

        unlink($testFile);
    }

    public function test_file_exists_returns_false_for_non_existing_file() {
        $nonExistingFile = '/path/to/non/existing/file.txt';
        $this->assertFalse(File::exists($nonExistingFile));
    }
}

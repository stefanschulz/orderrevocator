<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BuildTest extends TestCase
{
    public function testBuildExclusionsArePresent(): void
    {
        // The build.xml is in the root directory, three levels up from orderrevocator/tests/Unit/
        $buildXml = __DIR__ . '/../../../build.xml';
        $this->assertFileExists($buildXml);

        $content = file_get_contents($buildXml);

        $this->assertStringContainsString('exclude name="${folder}/tests/**"', $content, 'The tests directory must be excluded from the production package.');
        $this->assertStringContainsString('exclude name="${folder}/phpunit.xml.dist"', $content, 'The phpunit configuration file must be excluded from the production package.');
        $this->assertStringContainsString('exclude name="${folder}/.phpunit.result.cache"', $content, 'The PHPUnit result cache must be excluded from the production package.');
        $this->assertStringContainsString('exclude name="${folder}/coverage.xml"', $content, 'The Clover coverage report must be excluded from the production package.');
    }

    /**
     * Checks for stray test files that might have been accidentally left in the source
     * directory instead of tests/.
     */
    public function testNoTestFilesInSourceDirectory(): void
    {
        $sourceDir = __DIR__ . '/../../src';
        $this->assertDirectoryExists($sourceDir);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $fileName = $file->getFilename();
            $this->assertStringNotContainsString('Test', $fileName, "Production source file '{$fileName}' appears to be a test file and should not be in the src directory.");
        }
    }
}

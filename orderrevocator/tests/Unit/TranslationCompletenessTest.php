<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\OrderRevocator\Entity\Definitions;

/**
 * Guards against the exact class of bug this module has already shipped once:
 * a trans() call using a domain that its .xlf file doesn't actually cover, or
 * a string that was moved to a different domain in code but not in the
 * translation file. See commit 20778bc.
 */
class TranslationCompletenessTest extends TestCase
{
    private const string MODULE_ROOT = __DIR__ . '/../..';

    /**
     * @return array<int, array{source: string, domainConstant: string, file: string}>
     */
    public static function transCalls(): array
    {
        $files = array_merge(
            [self::MODULE_ROOT . '/orderrevocator.php'],
            self::phpFilesIn(self::MODULE_ROOT . '/controllers'),
            self::phpFilesIn(self::MODULE_ROOT . '/src')
        );

        $calls = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace(realpath(self::MODULE_ROOT) . DIRECTORY_SEPARATOR, '', realpath($file));

            if (preg_match_all(
                "/->trans\\(\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*,\\s*\\[]\\s*,\\s*Definitions::(TRANS_\\w+)\\s*\\)/",
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $calls[] = [
                        'source' => stripslashes($match[1]),
                        'domainConstant' => $match[2],
                        'file' => $relativeFile,
                    ];
                }
            }
        }

        return $calls;
    }

    /**
     * @return string[]
     */
    private static function phpFilesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file->isDir() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array<string, string> source string => target string
     */
    private function loadXliffSources(string $domain): array
    {
        $fileName = str_replace('.', '', $domain) . '.de-DE.xlf';
        $path = self::MODULE_ROOT . '/translations/de-DE/' . $fileName;

        $this->assertFileExists($path, "No translation file found for domain '{$domain}' (expected {$fileName}).");

        $doc = new DOMDocument();
        $doc->load($path);

        $entries = [];

        foreach ($doc->getElementsByTagName('trans-unit') as $unit) {
            $source = $unit->getElementsByTagName('source')->item(0)?->textContent;
            $target = $unit->getElementsByTagName('target')->item(0)?->textContent ?? '';

            if ($source !== null) {
                $entries[$source] = $target;
            }
        }

        return $entries;
    }

    /**
     * Every trans() call in the source code must have a matching <source> entry
     * in the .xlf file of the domain it actually uses.
     */
    public function testEveryTransCallHasATranslationEntryInItsOwnDomain(): void
    {
        $calls = self::transCalls();
        $this->assertNotEmpty($calls, 'Expected to find at least one trans() call - did the extraction regex break?');

        $xliffCache = [];

        foreach ($calls as $call) {
            $domain = constant(Definitions::class . '::' . $call['domainConstant']);
            $xliffCache[$domain] ??= $this->loadXliffSources($domain);

            $this->assertArrayHasKey(
                $call['source'],
                $xliffCache[$domain],
                sprintf(
                    "%s calls trans('%s', ..., Definitions::%s), but domain '%s' has no matching <source> entry. " .
                    'Either the string was moved to the wrong domain, or the translation file is out of date.',
                    $call['file'],
                    $call['source'],
                    $call['domainConstant'],
                    $domain
                )
            );
        }
    }

    /**
     * A <source> entry with an empty <target> is an untranslated string that
     * slipped into the .xlf file - worth catching before release.
     */
    public function testNoTranslationTargetIsEmpty(): void
    {
        foreach ([Definitions::TRANS_ADMIN, Definitions::TRANS_SHOP] as $domain) {
            foreach ($this->loadXliffSources($domain) as $source => $target) {
                $this->assertNotSame('', trim($target), "Domain '{$domain}': '{$source}' has an empty translation.");
            }
        }
    }
}

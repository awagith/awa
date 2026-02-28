#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Validates frontend layout XML files for custom modules and frontend themes.
 *
 * Checks:
 * - XML syntax (DOMDocument parse)
 * - Known anti-patterns that already broke layout merge in production:
 *   - <referenceblock> / <referencecontainer> (wrong casing)
 *   - xsi:nonamespaceschemalocation (wrong attribute casing)
 *   - stray ';' after XML attribute values
 *
 * Exit codes:
 * - 0: no findings
 * - 1: findings detected
 */

final class LayoutXmlValidator
{
    /**
     * @var list<string>
     */
    private array $roots;

    /**
     * @param list<string> $roots
     */
    public function __construct(array $roots)
    {
        $this->roots = $roots;
    }

    /**
     * @return array<string, list<string>>
     */
    public function run(): array
    {
        $findings = [];

        foreach ($this->collectFiles() as $file) {
            $issues = $this->validateFile($file);

            if ($issues !== []) {
                $findings[$file] = $issues;
            }
        }

        ksort($findings);

        return $findings;
    }

    /**
     * @return list<string>
     */
    private function collectFiles(): array
    {
        $files = [];

        foreach ($this->roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $entry) {
                if (!$entry->isFile()) {
                    continue;
                }

                $path = $entry->getPathname();

                if (pathinfo($path, PATHINFO_EXTENSION) !== 'xml') {
                    continue;
                }

                if (str_contains($path, '.bak.')) {
                    continue;
                }

                if ($this->shouldValidatePath($path)) {
                    $files[] = $path;
                }
            }
        }

        sort($files);

        return $files;
    }

    private function shouldValidatePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, 'app/code/')) {
            return preg_match('~^app/code/[^/]+/[^/]+/view/frontend/layout/[^/]+\.xml$~', $normalized) === 1;
        }

        if (str_starts_with($normalized, 'app/design/frontend/')) {
            return str_contains($normalized, '/layout/');
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function validateFile(string $file): array
    {
        $issues = [];
        $content = file_get_contents($file);

        if ($content === false) {
            return ['Unable to read file'];
        }

        $issues = array_merge($issues, $this->validateXmlSyntax($file));
        $issues = array_merge($issues, $this->findPatternIssues($content));

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function validateXmlSyntax(string $file): array
    {
        $issues = [];

        libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $loaded = $document->load($file);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if ($loaded) {
            return [];
        }

        foreach ($errors as $error) {
            $message = trim($error->message);
            $issues[] = sprintf('XML parse error (line %d): %s', $error->line, $message);
        }

        if ($issues === []) {
            $issues[] = 'XML parse error (unknown)';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function findPatternIssues(string $content): array
    {
        $issues = [];

        $patterns = [
            '~<referenceblock\b~' => 'Invalid lowercase tag <referenceblock>; use <referenceBlock>',
            '~<referencecontainer\b~' => 'Invalid lowercase tag <referencecontainer>; use <referenceContainer>',
            '~xsi:nonamespaceschemalocation~' => 'Invalid attribute xsi:nonamespaceschemalocation; use xsi:noNamespaceSchemaLocation',
            '~="[^\"]*";\s+(?=[a-zA-Z_:][a-zA-Z0-9:._-]*=)~' => 'Stray semicolon after XML attribute value',
        ];

        foreach ($patterns as $pattern => $message) {
            if (preg_match($pattern, $content) === 1) {
                $issues[] = $message;
            }
        }

        return $issues;
    }
}

$roots = [
    'app/design/frontend/AWA_Custom',
    'app/design/frontend/ayo',
    'app/code',
];

$validator = new LayoutXmlValidator($roots);
$findings = $validator->run();

if ($findings === []) {
    fwrite(STDOUT, "OK: no layout XML syntax/pattern issues found in app/design/frontend themes and app/code frontend layouts.\n");
    exit(0);
}

foreach ($findings as $file => $issues) {
    fwrite(STDOUT, $file . PHP_EOL);

    foreach ($issues as $issue) {
        fwrite(STDOUT, '  - ' . $issue . PHP_EOL);
    }
}

fwrite(STDOUT, sprintf("FAIL: %d file(s) with findings.\n", count($findings)));
exit(1);

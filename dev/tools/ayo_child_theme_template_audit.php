#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Auditoria de templates do child theme Ayo Home5.
 *
 * Regras (fail-fast por arquivo):
 * - sem superglobals em templates (`$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`)
 * - sem `var_dump` / `print_r` em templates
 * - sem `require([ ... ])` inline em PHTML (RequireJS inline)
 * - scripts inline só são permitidos se forem `type="text/x-magento-init"`
 * - `<script src=...>` é permitido (caso do loader controlado do child)
 */

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
}

function rootDir(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @return list<string>
 */
function collectTemplateFiles(string $childThemeRoot): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($childThemeRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $entry) {
        if (!$entry->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $entry->getPathname());
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($ext, ['phtml', 'php'], true)) {
            continue;
        }

        $files[] = $path;
    }

    sort($files);
    return $files;
}

/**
 * @return list<string>
 */
function auditFile(string $file): array
{
    $issues = [];
    $content = file_get_contents($file);

    if ($content === false) {
        return ['Falha ao ler arquivo'];
    }

    if (preg_match('/\$_(GET|POST|REQUEST|SERVER)\b/', $content) === 1) {
        $issues[] = 'Uso de superglobal detectado (use request/block/helper do Magento)';
    }

    if (preg_match('/\bvar_dump\s*\(/', $content) === 1) {
        $issues[] = 'Uso de var_dump detectado';
    }

    if (preg_match('/\bprint_r\s*\(/', $content) === 1) {
        $issues[] = 'Uso de print_r detectado';
    }

    if (str_ends_with($file, '.phtml') && preg_match('/require\s*\(\s*\[/', $content) === 1) {
        $issues[] = 'RequireJS inline detectado em PHTML (migrar para x-magento-init/AMD)';
    }

    if (str_ends_with($file, '.phtml')) {
        // Fechamento de bloco PHP em atributos HTML pode conter `>` e quebrar regex simples de tags.
        // Substituímos blocos PHP por placeholder antes de inspecionar <script>.
        $htmlish = preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i', '__PHP_BLOCK__', $content);
        if (!is_string($htmlish)) {
            $htmlish = $content;
        }

        if (preg_match_all('~<script\b([^>]*)>(.*?)</script>~is', $htmlish, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrs = (string) ($match[1] ?? '');
                $body = trim((string) ($match[2] ?? ''));
                $isMageInit = preg_match('~\btype\s*=\s*(["\'])text/x-magento-init\1~i', $attrs) === 1;
                $hasSrc = preg_match('~\bsrc\s*=\s*(["\']).+?\1~i', $attrs) === 1;

                if ($isMageInit) {
                    continue;
                }

                if ($hasSrc) {
                    if ($body !== '') {
                        $issues[] = '<script src=...> com conteúdo inline detectado';
                    }
                    continue;
                }

                $issues[] = '<script> inline executável detectado (use x-magento-init/AMD)';
                break;
            }
        }
    }

    return $issues;
}

try {
    $childThemeRoot = rootDir() . '/app/design/frontend/AWA_Custom/ayo_home5_child';
    if (!is_dir($childThemeRoot)) {
        throw new RuntimeException('Child theme root não encontrado: ' . $childThemeRoot);
    }

    out('=== AYO CHILD THEME TEMPLATE AUDIT ===');
    out('Root: ' . $childThemeRoot);

    $findings = [];
    foreach (collectTemplateFiles($childThemeRoot) as $file) {
        $issues = auditFile($file);
        if ($issues !== []) {
            $findings[$file] = $issues;
        }
    }

    if ($findings === []) {
        out('[OK] Nenhum anti-pattern de template detectado no child theme');
        exit(0);
    }

    out('');
    out('=== FALHAS ===');
    foreach ($findings as $file => $issues) {
        fail($file);
        foreach ($issues as $issue) {
            fail('  - ' . $issue);
        }
    }

    exit(1);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}

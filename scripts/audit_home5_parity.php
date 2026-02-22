#!/usr/bin/env php
<?php

declare(strict_types=1);

$envPath = __DIR__ . '/../app/etc/env.php';
if (!is_file($envPath)) {
    fwrite(STDERR, "[ERRO] env.php não encontrado\n");
    exit(1);
}

$env = include $envPath;
$db = $env['db']['connection']['default'] ?? null;
if (!is_array($db)) {
    fwrite(STDERR, "[ERRO] Config DB inválida no env.php\n");
    exit(1);
}

$host = (string) ($db['host'] ?? '127.0.0.1');
$dbname = (string) ($db['dbname'] ?? '');
$user = (string) ($db['username'] ?? '');
$pass = (string) ($db['password'] ?? '');

if ($dbname === '' || $user === '') {
    fwrite(STDERR, "[ERRO] Credenciais DB incompletas no env.php\n");
    exit(1);
}

$dsnCandidates = [];
if (str_starts_with($host, '/')) {
    $dsnCandidates[] = "mysql:unix_socket={$host};dbname={$dbname};charset=utf8mb4";
    $dsnCandidates[] = "mysql:host=127.0.0.1;port=3306;dbname={$dbname};charset=utf8mb4";
} else {
    $dsnCandidates[] = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    if ($host !== '127.0.0.1') {
        $dsnCandidates[] = "mysql:host=127.0.0.1;port=3306;dbname={$dbname};charset=utf8mb4";
    }
}

$pdo = null;
$lastError = null;
foreach ($dsnCandidates as $dsn) {
    try {
        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        echo "[OK] Conectado no MySQL via DSN: {$dsn}\n";
        break;
    } catch (Throwable $e) {
        $lastError = $e->getMessage();
    }
}

if (!$pdo instanceof PDO) {
    fwrite(STDERR, "[ERRO] Falha ao conectar no MySQL: {$lastError}\n");
    exit(1);
}

$requiredBlocks = [
    'block_top',
    'banner_mid_home5',
    'notification_home5',
    'category1_home5',
    'category2_home5',
    'footer_static5',
    'footer_payment',
    'fixed_right',
];

echo "\n=== HOME PAGE CONFIG ===\n";
$homeRows = $pdo->query(
    "SELECT scope, scope_id, path, value
     FROM core_config_data
     WHERE path IN ('web/default/cms_home_page', 'design/theme/theme_id')
     ORDER BY path, scope, scope_id"
)->fetchAll();

if ($homeRows === []) {
    echo "(sem registros em core_config_data para home/theme)\n";
} else {
    foreach ($homeRows as $row) {
        echo sprintf(
            "- %s[%s] %s = %s\n",
            $row['scope'],
            $row['scope_id'],
            $row['path'],
            (string) $row['value']
        );
    }
}

echo "\n=== CMS BLOCKS HOME5 ===\n";
$in = implode(',', array_fill(0, count($requiredBlocks), '?'));
$stmt = $pdo->prepare(
    "SELECT identifier, is_active, title
     FROM cms_block
     WHERE identifier IN ({$in})
     ORDER BY identifier"
);
$stmt->execute($requiredBlocks);
$rows = $stmt->fetchAll();

$found = [];
foreach ($rows as $row) {
    $found[(string) $row['identifier']] = true;
    echo sprintf(
        "- %s | active=%s | %s\n",
        $row['identifier'],
        $row['is_active'],
        $row['title']
    );
}

$missing = array_values(array_filter($requiredBlocks, static fn(string $id): bool => !isset($found[$id])));
if ($missing !== []) {
    echo "\n[WARN] Blocos ausentes: " . implode(', ', $missing) . "\n";
} else {
    echo "\n[OK] Todos os blocos CMS críticos da Home5 estão presentes.\n";
}

echo "\n=== SLIDER (best effort) ===\n";
$sliderTable = null;
$candidates = ['rokanthemes_slide', 'rokanthemes_slider', 'rokanthemes_slidebanner'];
foreach ($candidates as $table) {
    $exists = $pdo->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $exists->execute([$table]);
    if ((int) ($exists->fetch()['c'] ?? 0) > 0) {
        $sliderTable = $table;
        break;
    }
}

if ($sliderTable === null) {
    echo "- tabela de slider não identificada automaticamente.\n";
} else {
    echo "- tabela detectada: {$sliderTable}\n";
    $sliderRows = $pdo->query("SELECT * FROM {$sliderTable} LIMIT 5")->fetchAll();
    echo "- amostra de registros: " . count($sliderRows) . "\n";
}

echo "\n[FIM] Auditoria de paridade Home5 concluída.\n";

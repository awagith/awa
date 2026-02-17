#!/usr/bin/env php
<?php
/**
 * Diagnóstico de OpenSearch para Magento.
 * Uso:
 *   php scripts/opensearch_diagnose.php [--host 127.0.0.1] [--port 9200] [--scheme http] [--prefix loja] [--timeout 3]
 */
declare(strict_types=1);

error_reporting(E_ALL);

// Parse argumentos simples
$args = $argv;
array_shift($args);
$options = [
    'host' => '127.0.0.1',
    'port' => '9200',
    'scheme' => 'http',
    'prefix' => '',
    'timeout' => '3'
];
foreach ($args as $i => $arg) {
    if (strpos($arg, '--') === 0) {
        $key = substr($arg, 2);
        $next = $args[$i + 1] ?? null;
        if ($next !== null && strpos($next, '--') !== 0) {
            $options[$key] = $next;
        } elseif ($key && !isset($options[$key])) {
            $options[$key] = '1';
        }
    }
}

require __DIR__ . '/../app/bootstrap.php';
use Magento\Framework\App\Bootstrap;

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();
/** @var Magento\Framework\App\State $state */
$state = $objectManager->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}
/** @var Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
$scopeConfig = $objectManager->get(Magento\Framework\App\Config\ScopeConfigInterface::class);

$engine = (string)$scopeConfig->getValue('catalog/search/engine');
$confHost = (string)$scopeConfig->getValue('catalog/search/opensearch_server_hostname');
$confPort = (string)$scopeConfig->getValue('catalog/search/opensearch_server_port');
$confPrefix = (string)$scopeConfig->getValue('catalog/search/index_prefix');

// Override com args CLI se fornecidos
if (!empty($options['host'])) { $confHost = $options['host']; }
if (!empty($options['port'])) { $confPort = $options['port']; }
if (!empty($options['prefix'])) { $confPrefix = $options['prefix']; }
if (!empty($options['scheme'])) { $scheme = $options['scheme']; } else { $scheme = 'http'; }
$timeout = (int)$options['timeout'];

$base = sprintf('%s://%s:%s', $scheme, $confHost ?: '127.0.0.1', $confPort ?: '9200');

function curlJson(string $url, int $timeout = 3): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'http' => $code, 'error' => $err ?: 'curl_exec failed'];
    }
    $json = json_decode($raw, true);
    return ['ok' => $code >= 200 && $code < 300, 'http' => $code, 'data' => $json, 'raw' => $raw, 'error' => $err];
}

echo "=== Magento Config ===\n";
echo "Engine: $engine\nHost: $confHost\nPort: $confPort\nPrefix: $confPrefix\nBase URL: $base\n\n";

echo "=== Ping / cluster info ===\n";
$ping = curlJson($base, $timeout);
if (!$ping['ok']) {
    echo "Falha ao conectar: HTTP {$ping['http']} Erro: {$ping['error']}\n";
    echo "Sugestões:\n - Verifique firewall/porta aberta.\n - Se está usando host local sem serviço, suba um container OpenSearch.\n - Ajuste host/porta: php scripts/opensearch_diagnose.php --host seu.host --port 443 --scheme https\n";
    exit(2);
}
echo "Cluster response HTTP {$ping['http']}\n";
if (isset($ping['data']['cluster_name'])) {
    echo "Cluster: {$ping['data']['cluster_name']} Versão: " . ($ping['data']['version']['number'] ?? 'n/d') . "\n";
}
echo "\n=== Health ===\n";
$health = curlJson($base . '/_cluster/health', $timeout);
if ($health['ok']) {
    echo "Status: {$health['data']['status']} Nodes: {$health['data']['number_of_nodes']} DataNodes: {$health['data']['number_of_data_nodes']}\n";
} else {
    echo "Falha health: HTTP {$health['http']} {$health['error']}\n";
}

echo "\n=== Índices do prefixo (" . ($confPrefix ?: '*') . ") ===\n";
$pattern = $confPrefix ? $confPrefix . '*': '*';
$cat = curlJson($base . '/_cat/indices/' . $pattern . '?h=index,health,status,docs.count,store.size', $timeout);
if ($cat['ok']) {
    echo trim($cat['raw']) . "\n";
    if (trim($cat['raw']) === '') {
        echo "Nenhum índice com prefixo atual. Depois de corrigir conexão, execute: php bin/magento indexer:reindex catalogsearch_fulltext\n";
    }
} else {
    echo "Falha listar índices: HTTP {$cat['http']} {$cat['error']}\n";
}

echo "\n=== Próximos Passos ===\n";
if ($engine !== 'opensearch') {
    echo "1. Defina engine para opensearch: php bin/magento config:set catalog/search/engine opensearch\n";
}
echo "2. Se conexão OK: reindexar: php bin/magento indexer:reindex catalogsearch_fulltext\n";
echo "3. Limpar cache: php bin/magento cache:flush\n";
echo "4. Se falha de versão, garanta OpenSearch >= 2.x compatível com Magento.\n";
echo "5. Produção: considere usuário/password ou SSL (ajuste scheme/host).\n";

exit(0);

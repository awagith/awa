<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', [
    'engine:',            // opensearch | elasticsearch7
    'host:',              // hostname ou IP
    'port:',              // porta
    'scheme::',           // http | https (default http)
    'prefix::',           // index prefix (default magento2)
    'auth::',             // 0|1 (default 0)
    'user::',             // username
    'pass::',             // password
    'test::',             // 0|1 faz requisição de teste
    'disable-fallback::', // 0|1 desativa mini-search fallback customizado
]);

function need(array $o, string $k): string {
    if (!isset($o[$k]) || $o[$k] === '') {
        fwrite(STDERR, "Uso: php scripts/configure_search.php --engine opensearch|elasticsearch7 --host <host> --port <port> [--scheme http|https] [--prefix <prefix>] [--auth 0|1] [--user <u>] [--pass <p>] [--test 1] [--disable-fallback 1]\n");
        exit(2);
    }
    return (string)$o[$k];
}

$engine = strtolower(need($opts, 'engine'));
if (!in_array($engine, ['opensearch', 'elasticsearch7'], true)) {
    fwrite(STDERR, "Engine inválido. Use 'opensearch' ou 'elasticsearch7'.\n");
    exit(2);
}
$host = need($opts, 'host');
$port = need($opts, 'port');
$scheme = isset($opts['scheme']) && $opts['scheme'] !== '' ? (string)$opts['scheme'] : 'http';
$prefix = isset($opts['prefix']) && $opts['prefix'] !== '' ? (string)$opts['prefix'] : 'magento2';
$auth   = isset($opts['auth']) ? ((string)$opts['auth'] === '1' ? 1 : 0) : 0;
$user   = isset($opts['user']) ? (string)$opts['user'] : '';
$pass   = isset($opts['pass']) ? (string)$opts['pass'] : '';

// Função de teste de conexão simples
function testConnection(string $scheme, string $host, string $port, ?string $user, ?string $pass): array {
    $url = sprintf('%s://%s:%s', $scheme, $host, $port);
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($user && $pass) {
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = $raw ? json_decode($raw, true) : null;
    return ['code' => $code, 'error' => $err, 'body' => $json, 'raw' => $raw];
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\Config\Storage\WriterInterface $writer */
$writer = $om->get(Magento\Framework\App\Config\Storage\WriterInterface::class);
/** @var Magento\Framework\App\Cache\TypeListInterface $typeList */
$typeList = $om->get(Magento\Framework\App\Cache\TypeListInterface::class);

// Definir engine
$writer->save('catalog/search/engine', $engine);

// Chaves específicas por engine
if ($engine === 'opensearch') {
    $writer->save('catalog/search/opensearch_server_hostname', $host);
    $writer->save('catalog/search/opensearch_server_port', $port);
    $writer->save('catalog/search/opensearch_server_scheme', $scheme);
    $writer->save('catalog/search/opensearch_index_prefix', $prefix);
    $writer->save('catalog/search/opensearch_enable_auth', (string)$auth);
    if ($auth) {
        $writer->save('catalog/search/opensearch_username', $user);
        $writer->save('catalog/search/opensearch_password', $pass);
    }
} else { // elasticsearch7
    $writer->save('catalog/search/elasticsearch7_server_hostname', $host);
    $writer->save('catalog/search/elasticsearch7_server_port', $port);
    $writer->save('catalog/search/elasticsearch7_server_scheme', $scheme);
    $writer->save('catalog/search/elasticsearch7_index_prefix', $prefix);
    $writer->save('catalog/search/elasticsearch7_enable_auth', (string)$auth);
    if ($auth) {
        $writer->save('catalog/search/elasticsearch7_username', $user);
        $writer->save('catalog/search/elasticsearch7_password', $pass);
    }
}

// Limpar cache de config
foreach (['config', 'full_page'] as $type) {
    try { $typeList->cleanType($type); } catch (\Throwable $e) {}
}

// Testar conexão se solicitado
$doTest = isset($opts['test']) && (string)$opts['test'] === '1';
if ($doTest) {
    $result = testConnection($scheme, $host, $port, $auth ? $user : null, $auth ? $pass : null);
    if ($result['code'] < 200 || $result['code'] >= 300) {
        fwrite(STDERR, "[TEST] Falha conexão $scheme://$host:$port HTTP={$result['code']} Err={$result['error']}\n");
    } else {
        $version = $result['body']['version']['number'] ?? 'n/d';
        echo "[TEST] Conexão OK. Versão: $version\n";
    }
}

// Opcionalmente desativar fallback mini-search
if (isset($opts['disable-fallback']) && (string)$opts['disable-fallback'] === '1') {
    $writer->save('grupoawamotos_fitment/search/fallback_enabled', '0');
}

echo "Configuração de busca aplicada para $engine em $scheme://$host:$port prefix '$prefix'\n";
echo "Reindex: php bin/magento indexer:reindex catalogsearch_fulltext\n";
echo "Se falhar: use scripts/opensearch_diagnose.php para detalhes ou verifique versão e auth.\n";
if (isset($opts['disable-fallback']) && (string)$opts['disable-fallback'] === '1') {
    echo "Fallback desativado (grupoawamotos_fitment/search/fallback_enabled=0).\n";
}
exit(0);

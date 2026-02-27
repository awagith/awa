<?php
declare(strict_types=1);

/**
 * Script para decriptar senha do ERP e testar conexão SQL Server
 * Usa MySQL direto (sem Magento bootstrap pesado) + decriptação manual
 *
 * Uso: php scripts/test_erp_decrypt_connect.php
 */

echo "====================================================\n";
echo "  DECRIPTAR SENHA ERP + TESTAR CONEXAO SQL SERVER\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n\n";

// 1. Ler env.php
$envFile = dirname(__DIR__) . '/app/etc/env.php';
if (!file_exists($envFile)) {
    echo "ERRO: app/etc/env.php nao encontrado\n";
    exit(1);
}

$env = include $envFile;
$cryptKey = $env['crypt']['key'] ?? '';
$db = $env['db']['connection']['default'] ?? [];

echo "== LENDO CONFIGURACAO ==\n";
echo "  Crypt key: " . substr($cryptKey, 0, 15) . "...\n";

// 2. Conectar no MySQL
$dbHost = $db['host'] ?? 'localhost';
$dbName = $db['dbname'] ?? 'magento';
$dbUser = $db['username'] ?? '';
$dbPass = $db['password'] ?? '';
$socket = $db['unix_socket'] ?? '';

try {
    $dsn = $socket
        ? "mysql:unix_socket=$socket;dbname=$dbName"
        : "mysql:host=$dbHost;dbname=$dbName";
    $mysql = new PDO($dsn, $dbUser, $dbPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "  MySQL: conectado\n";
} catch (\PDOException $e) {
    echo "  ERRO MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Buscar configs do ERP
$sql = "SELECT path, value FROM core_config_data WHERE path LIKE 'grupoawamotos_erp/connection/%'";
$stmt = $mysql->query($sql);
$configs = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = str_replace('grupoawamotos_erp/connection/', '', $row['path']);
    $configs[$key] = $row['value'];
}

$host     = $configs['host'] ?? '';
$port     = (int) ($configs['port'] ?? 1433);
$database = $configs['database'] ?? '';
$username = $configs['username'] ?? '';
$encryptedPassword = $configs['password'] ?? '';

echo "\n== DADOS DO ERP ==\n";
echo "  Host:     $host\n";
echo "  Porta:    $port\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
echo "  Senha:    [encriptada] " . substr($encryptedPassword, 0, 30) . "...\n";

// 4. Decriptar a senha usando a mesma lógica do Magento
echo "\n== DECRIPTANDO SENHA ==\n";

$password = magentoDecrypt($encryptedPassword, $cryptKey);

if ($password === null || $password === false || $password === '') {
    echo "  FALHA na decriptacao padrao. Tentando metodos alternativos...\n";
    
    // Tenta usar o Magento Encryptor via autoload
    $autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        
        try {
            // Instancia o Encryptor do Magento diretamente
            $deployConfig = new \Magento\Framework\App\DeploymentConfig\Reader(
                new \Magento\Framework\App\Filesystem\DirectoryList(dirname(__DIR__)),
                null,
                null,
                \Magento\Framework\Config\File\ConfigFilePool::APP_ENV
            );
            
            // Método mais direto: usar a classe Encryptor
            $encryptor = new \Magento\Framework\Encryption\Encryptor(
                new \Magento\Framework\Math\Random(),
                new \Magento\Framework\App\DeploymentConfig(
                    new \Magento\Framework\App\DeploymentConfig\Reader(
                        new \Magento\Framework\App\Filesystem\DirectoryList(dirname(__DIR__))
                    )
                )
            );
            
            $password = $encryptor->decrypt($encryptedPassword);
            if (!empty($password)) {
                echo "  [OK] Senha decriptada via Magento Encryptor!\n";
            }
        } catch (\Throwable $e) {
            echo "  Encryptor falhou: " . $e->getMessage() . "\n";
        }
    }
    
    // Último recurso: tenta a decriptação de baixo nível
    if (empty($password)) {
        $password = magentoDecryptLowLevel($encryptedPassword, $cryptKey);
    }
    
    if (empty($password)) {
        echo "  ERRO: Nao foi possivel decriptar a senha.\n\n";
        echo "  Passe a senha manualmente:\n";
        echo "  ERP_SQL_PASSWORD=senha php scripts/test_erp_standalone.php\n";
        exit(1);
    }
}

echo "  [OK] Senha decriptada: " . str_repeat('*', min(strlen($password), 8)) . " (" . strlen($password) . " chars)\n";

// 5. Testar rede
echo "\n== TESTE DE REDE ==\n";
// Testar ambas as portas
$portsToTest = array_unique([$port, 3387, 1433]);
$openPorts = [];
foreach ($portsToTest as $p) {
    $sock = @fsockopen($host, $p, $errno, $errstr, 3);
    if ($sock) {
        echo "  [OK] TCP $host:$p acessivel\n";
        fclose($sock);
        $openPorts[] = $p;
    } else {
        echo "  [FALHA] TCP $host:$p inacessivel\n";
    }
}

if (empty($openPorts)) {
    echo "  ERRO: Nenhuma porta acessivel!\n";
    exit(1);
}

// 6. Testar conexão SQL Server
echo "\n== TESTANDO CONEXAO SQL SERVER ==\n";

$pdo = null;
$driverUsed = '';

// Lista de tentativas: alias FreeTDS + portas + versões TDS
$attempts = [];

// FreeTDS aliases primeiro (já têm porta e TDS configurados no freetds.conf)
$aliases = ['ERPLOCAL', 'ERPLOCAL_OFF', 'ERPLOCAL_ENC', 'ERPLOCAL_73', 'ERPLOCAL_72'];
foreach ($aliases as $alias) {
    $attempts[] = ['host' => $alias, 'port' => null, 'tds' => null, 'label' => "alias [$alias]"];
}

// Porta configurada + portas alternativas
foreach ($openPorts as $p) {
    foreach (['7.4', '7.3', '7.2', '7.1', '7.0'] as $tds) {
        $attempts[] = ['host' => $host, 'port' => $p, 'tds' => $tds, 'label' => "IP:$p TDS=$tds"];
    }
}

foreach ($attempts as $attempt) {
    $h = $attempt['host'];
    $p = $attempt['port'];
    $tds = $attempt['tds'];
    $label = $attempt['label'];
    
    echo "  Tentando $label... ";
    
    if ($tds) {
        putenv("TDSVER=$tds");
    }
    
    try {
        if ($p) {
            $dsn = "dblib:host=$h:$p;dbname=$database;charset=UTF-8";
        } else {
            $dsn = "dblib:host=$h;dbname=$database;charset=UTF-8";
        }
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        
        $driverUsed = $label;
        echo "OK!\n";
        break;
    } catch (\PDOException $e) {
        $msg = substr($e->getMessage(), 0, 50);
        echo "FALHA";
        // Mostrar erro detalhado se for algo diferente de "Adaptive Server connection failed"
        if (stripos($msg, 'Adaptive Server') === false) {
            echo " - $msg";
        }
        echo "\n";
        $pdo = null;
    }
}

if ($pdo === null) {
    echo "\n  ERRO: Nenhuma combinacao funcionou!\n";
    echo "\n  Debug: tente manualmente com tsql:\n";
    echo "  tsql -S ERPLOCAL -U $username -P '$password'\n";
    echo "  tsql -H $host -p 3387 -U $username -P '$password'\n\n";
    echo "  Se tsql tambem falhar, o problema e no SQL Server / firewall / credenciais.\n";
    exit(1);
}

echo "\n  [CONECTADO] Via: $driverUsed\n";

// Server info
try {
    $row = $pdo->query("SELECT @@VERSION AS v, @@SERVERNAME AS s, DB_NAME() AS d")->fetch();
    echo "  Servidor: " . ($row['s'] ?? '?') . "\n";
    echo "  Database: " . ($row['d'] ?? '?') . "\n";
    echo "  Versao:   " . substr(($row['v'] ?? '?'), 0, 80) . "\n";
} catch (\Exception $e) {
    // silent
}

// 7. Quick table check
echo "\n== TABELAS ==\n";
$tables = ['MT_MATERIAL', 'MT_ESTOQUEMEDIA', 'MT_MATERIALLISTA', 'FN_FORNECEDORES', 
           'VE_PEDIDO', 'GR_INTEGRACAOVALIDADOR', 'MT_GRUPOCOMERCIAL'];
$ok = 0;
foreach ($tables as $table) {
    try {
        $pdo->query("SELECT TOP 1 * FROM $table")->fetch();
        echo "  [OK] $table\n";
        $ok++;
    } catch (\Exception $e) {
        echo "  [FALHA] $table\n";
    }
}
echo "\n  $ok/" . count($tables) . " tabelas acessiveis\n";

// 8. Quick counts
echo "\n== VOLUMETRIA ==\n";
$counts = [
    'Produtos ativos' => "SELECT COUNT(*) c FROM MT_MATERIAL WHERE CCKATIVO='S'",
    'Estoque' => "SELECT COUNT(*) c FROM MT_ESTOQUEMEDIA",
    'Clientes' => "SELECT COUNT(*) c FROM FN_FORNECEDORES WHERE CKCLIENTE='S'",
    'Pedidos' => "SELECT COUNT(*) c FROM VE_PEDIDO",
];
foreach ($counts as $label => $sql) {
    try {
        $r = $pdo->query($sql)->fetch();
        echo "  $label: " . number_format((int)($r['c'] ?? 0), 0, ',', '.') . "\n";
    } catch (\Exception $e) {
        echo "  $label: erro\n";
    }
}

echo "\n====================================================\n";
echo "  SUCESSO! Conexao ERP Sectra funcionando.\n";

// Mostrar config recomendada
if ($driverUsed) {
    if (str_contains($driverUsed, 'alias')) {
        preg_match('/\[(\w+)\]/', $driverUsed, $m);
        $alias = $m[1] ?? 'ERPLOCAL';
        echo "\n  CONFIG RECOMENDADA (Admin > Config > AWA > ERP):\n";
        echo "  Host: $alias (alias FreeTDS)\n";
        echo "  Porta: (deixe vazio, FreeTDS resolve)\n";
    } elseif (preg_match('/IP:(\d+)/', $driverUsed, $m)) {
        $workingPort = $m[1];
        if ((int)$workingPort !== $port) {
            echo "\n  *** ATENCAO: A porta correta e $workingPort (configurada: $port) ***\n";
            echo "  Atualize em Admin > Config > AWA > ERP > Porta\n";
        }
    }
}

echo "====================================================\n";

// ============================================================
// FUNCOES DE DECRIPTACAO (replica exata do Magento Encryptor)
// ============================================================

/**
 * Decripta dados encriptados pelo Magento Framework Encryptor.
 *
 * Formatos suportados:
 *   4 partes: keyVersion:cryptVersion:iv:data → Rijndael 256 CBC
 *   3 partes: keyVersion:cryptVersion:data    → por cryptVersion
 *   2 partes: cryptVersion:data               → keyVersion=0
 *   1 parte:  data                            → Blowfish ECB
 *
 * CryptVersion 0 = Blowfish, 1 = Rijndael128, 2 = Rijndael256, 3 = SodiumChachaIetf
 */
function magentoDecrypt(string $data, string $cryptKey): ?string
{
    if (empty($data)) {
        return '';
    }
    
    $key = decodeKey($cryptKey);
    
    echo "  Debug: key decodificada = " . strlen($key) . " bytes\n";
    echo "  Debug: dados encriptados = " . substr($data, 0, 40) . "...\n";
    
    $parts = explode(':', $data, 4);
    $partsCount = count($parts);
    
    $initVector = null;
    $keyVersion = 0;
    $cryptVersion = 0;
    
    if ($partsCount === 4) {
        // keyVersion:cryptVersion:iv:data → Magento força Rijndael 256 CBC
        [$keyVersion, $cryptVersion, $iv, $data] = $parts;
        $initVector = !empty($iv) ? $iv : null;
        $keyVersion = (int) $keyVersion;
        $cryptVersion = 2; // CIPHER_RIJNDAEL_256 — Magento força este valor!
        echo "  Debug: formato 4 partes (keyVer=$keyVersion, cipher=Rijndael256, iv=" . strlen($iv) . " chars)\n";
    } elseif ($partsCount === 3) {
        // keyVersion:cryptVersion:data
        [$keyVersion, $cryptVersion, $data] = $parts;
        $keyVersion = (int) $keyVersion;
        $cryptVersion = (int) $cryptVersion;
        echo "  Debug: formato 3 partes (keyVer=$keyVersion, cipher=$cryptVersion)\n";
    } elseif ($partsCount === 2) {
        [$cryptVersion, $data] = $parts;
        $keyVersion = 0;
        $cryptVersion = (int) $cryptVersion;
        echo "  Debug: formato 2 partes (cipher=$cryptVersion)\n";
    } elseif ($partsCount === 1) {
        $keyVersion = 0;
        $cryptVersion = 0; // Blowfish ECB
        echo "  Debug: formato 1 parte (Blowfish)\n";
    } else {
        return null;
    }
    
    // Cipher 3 = SodiumChachaIetf
    if ($cryptVersion >= 3) {
        echo "  Debug: usando SodiumChachaIetf\n";
        return decryptSodiumChacha($data, $key);
    }
    
    // Legacy ciphers (OpenSSL / Mcrypt emulation)
    return decryptLegacy($data, $key, $cryptVersion, $initVector);
}

/**
 * Decodifica a chave — strip prefixo 'base64' e base64_decode
 */
function decodeKey(string $key): string
{
    $prefix = 'base64';
    if (str_starts_with($key, $prefix)) {
        $decoded = base64_decode(substr($key, strlen($prefix)));
        return $decoded !== false ? $decoded : $key;
    }
    return $key;
}

/**
 * SodiumChachaIetf decrypt (exatamente como Magento\Framework\Encryption\Adapter\SodiumChachaIetf)
 * 
 * O dado base64-decodificado contém: nonce (12 bytes) + ciphertext+tag
 * A ad (additional data) = nonce (mesmo valor)
 */
function decryptSodiumChacha(string $base64Data, string $key): ?string
{
    $raw = base64_decode($base64Data);
    if ($raw === false) {
        echo "  Debug: base64_decode falhou\n";
        return null;
    }
    
    $nonceLen = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES; // 12
    echo "  Debug: raw data = " . strlen($raw) . " bytes, nonce = $nonceLen bytes\n";
    
    if (strlen($raw) <= $nonceLen) {
        echo "  Debug: dados curtos demais\n";
        return null;
    }
    
    $nonce = mb_substr($raw, 0, $nonceLen, '8bit');
    $payload = mb_substr($raw, $nonceLen, null, '8bit');
    
    echo "  Debug: nonce = " . bin2hex($nonce) . "\n";
    echo "  Debug: payload = " . strlen($payload) . " bytes\n";
    echo "  Debug: key len = " . strlen($key) . " bytes\n";
    
    // A chave precisa ter exatamente SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES (32) bytes
    $keyLen = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;
    if (strlen($key) !== $keyLen) {
        echo "  Debug: ajustando key de " . strlen($key) . " para $keyLen bytes\n";
        $key = substr(str_pad($key, $keyLen, "\0"), 0, $keyLen);
    }
    
    try {
        // Magento usa nonce como additional data E como nonce
        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $payload,
            $nonce,  // ad = nonce
            $nonce,  // nonce
            $key
        );
        
        if ($plaintext !== false && $plaintext !== '') {
            return trim($plaintext);
        }
        echo "  Debug: sodium retornou false/vazio\n";
    } catch (\SodiumException $e) {
        echo "  Debug: sodium exception: " . $e->getMessage() . "\n";
    }
    
    return null;
}

/**
 * Decrypt via legacy OpenSSL ciphers (Mcrypt emulation)
 */
function decryptLegacy(string $base64Data, string $key, int $cryptVersion, ?string $initVector): ?string
{
    $raw = base64_decode($base64Data);
    if ($raw === false || $raw === '') {
        return null;
    }
    
    $iv = ($initVector !== null) ? base64_decode($initVector) : null;
    
    // Mapear cipher version para OpenSSL method
    // Nota: Magento usa phpseclib/mcrypt-compat ou mcrypt nativo
    // ECB modes não usam IV
    $cipherMap = [
        0 => ['method' => 'bf-ecb', 'needsIv' => false],     // Blowfish ECB
        1 => ['method' => 'aes-128-ecb', 'needsIv' => false], // Rijndael128 ECB 
        2 => ['method' => 'aes-256-cbc', 'needsIv' => true],  // Rijndael256 CBC
    ];
    
    $cipher = $cipherMap[$cryptVersion] ?? $cipherMap[0];
    $method = $cipher['method'];
    
    echo "  Debug: legacy cipher=$method, iv=" . ($iv ? strlen($iv) . " bytes" : "null") . "\n";
    
    $ivLen = openssl_cipher_iv_length($method);
    $useIv = '';
    if ($cipher['needsIv'] && $ivLen > 0) {
        if ($iv !== null) {
            $useIv = str_pad(substr($iv, 0, $ivLen), $ivLen, "\0");
        } else {
            $useIv = str_repeat("\0", $ivLen);
        }
    }
    
    $decrypted = @openssl_decrypt(
        $raw,
        $method,
        $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $useIv
    );
    
    if ($decrypted !== false && $decrypted !== '') {
        return rtrim($decrypted, "\0");
    }
    
    // Tenta sem ZERO_PADDING
    $decrypted = @openssl_decrypt(
        $raw,
        $method,
        $key,
        OPENSSL_RAW_DATA,
        $useIv
    );
    
    if ($decrypted !== false && $decrypted !== '') {
        return rtrim($decrypted, "\0");
    }
    
    echo "  Debug: openssl_decrypt falhou\n";
    return null;
}

/**
 * Tenta decriptacao de baixo nivel com todas as combinações
 */
function magentoDecryptLowLevel(string $data, string $cryptKey): ?string
{
    $key = decodeKey($cryptKey);
    $parts = explode(':', $data, 4);
    
    // Extrair dados
    if (count($parts) === 4) {
        $base64Data = $parts[3];
    } elseif (count($parts) === 3) {
        $base64Data = $parts[2];
    } elseif (count($parts) === 2) {
        $base64Data = $parts[1];
    } else {
        $base64Data = $data;
    }
    
    $raw = base64_decode($base64Data);
    if ($raw === false) {
        return null;
    }
    
    // Tenta Sodium primeiro (mais provável em Magento 2.4+)
    if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_decrypt')) {
        $nonceLen = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($raw) > $nonceLen) {
            $nonce = mb_substr($raw, 0, $nonceLen, '8bit');
            $payload = mb_substr($raw, $nonceLen, null, '8bit');
            $keyLen = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;
            $useKey = substr(str_pad($key, $keyLen, "\0"), 0, $keyLen);
            
            try {
                $result = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($payload, $nonce, $nonce, $useKey);
                if ($result !== false && $result !== '') {
                    echo "  [OK] Decriptado com sodium_chacha20poly1305_ietf (brute)\n";
                    return trim($result);
                }
            } catch (\Exception $e) {
                // continue
            }
        }
    }
    
    // Tenta OpenSSL ciphers
    $methods = ['aes-256-cbc', 'aes-128-cbc', 'aes-256-ecb', 'aes-128-ecb', 'bf-ecb', 'bf-cbc'];
    foreach ($methods as $method) {
        $ivLen = openssl_cipher_iv_length($method);
        $useIv = ($ivLen > 0) ? str_repeat("\0", $ivLen) : '';
        
        foreach ([OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, OPENSSL_RAW_DATA] as $flags) {
            $dec = @openssl_decrypt($raw, $method, $key, $flags, $useIv);
            if ($dec !== false && $dec !== '' && isPrintable($dec)) {
                echo "  [OK] Decriptado com $method (brute)\n";
                return rtrim($dec, "\0");
            }
        }
    }
    
    return null;
}

function isPrintable(string $str): bool
{
    $clean = rtrim($str, "\0");
    if (strlen($clean) === 0) {
        return false;
    }
    $printable = 0;
    $len = strlen($clean);
    for ($i = 0; $i < $len; $i++) {
        $ord = ord($clean[$i]);
        if ($ord >= 32 && $ord <= 126) {
            $printable++;
        }
    }
    return ($printable / $len) > 0.8;
}

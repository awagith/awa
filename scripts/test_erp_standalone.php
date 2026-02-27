<?php
declare(strict_types=1);

/**
 * Teste STANDALONE de conectividade com ERP Sectra (SQL Server)
 * NÃO depende do Magento bootstrap (MySQL/Redis)
 *
 * Uso no servidor:
 *   php scripts/test_erp_standalone.php
 *
 * Ou com credenciais manuais:
 *   ERP_SQL_HOST=192.168.x.x ERP_SQL_PORT=1433 ERP_SQL_DATABASE=SectraDB \
 *   ERP_SQL_USERNAME=magento_read ERP_SQL_PASSWORD=xxx \
 *   php scripts/test_erp_standalone.php
 */

echo "====================================================\n";
echo "  TESTE DE CONECTIVIDADE ERP SECTRA (SQL Server)\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n\n";

// ============================================================
// Helper: Decriptar senha encriptada pelo Magento
// Formato: <hash_version>:<cipher_version>:<iv_base64>:<encrypted_base64>
// ============================================================
function magentoDecrypt(string $data, string $cryptKey): ?string
{
    // Se não parece encriptada (não tem formato X:X:...), retorna null
    $parts = explode(':', $data);
    if (count($parts) < 3) {
        return null; // provavelmente texto plano
    }

    // Remove prefixo "base64" da chave se presente
    $key = $cryptKey;
    if (str_starts_with($key, 'base64')) {
        $key = base64_decode(substr($key, 6));
    }

    // Magento 2 format: hash_version:cipher_version:iv:encrypted
    // hash_version: 0 = md5, 1 = sha256
    // cipher_version: 0 = blowfish, 1 = aes-128, 2 = aes-256, 3 = sodium
    if (count($parts) === 4) {
        // New format: hash:cipher:iv:data
        [, $cipherVersion, $ivBase64, $encryptedBase64] = $parts;
    } elseif (count($parts) === 3) {
        // Old format: cipher:iv:data
        [$cipherVersion, $ivBase64, $encryptedBase64] = $parts;
    } else {
        return null;
    }

    $iv = base64_decode($ivBase64);
    $encrypted = base64_decode($encryptedBase64);
    $cipherVersion = (int) $cipherVersion;

    // Determine cipher method
    switch ($cipherVersion) {
        case 0:
            $method = 'bf-ecb';
            break;
        case 1:
            $method = 'aes-128-cbc';
            break;
        case 2:
            $method = 'aes-256-cbc';
            break;
        case 3:
            // Sodium - try sodium_crypto_aead_xchacha20poly1305_ietf_decrypt
            if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
                try {
                    // For sodium, it's: nonce + ciphertext
                    $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                        $encrypted,
                        '',
                        $iv,
                        substr(hash('sha256', (string) $key, true), 0, 32)
                    );
                    return $decrypted !== false ? rtrim($decrypted, "\0") : null;
                } catch (\Exception $e) {
                    return null;
                }
            }
            return null;
        default:
            return null;
    }

    // OpenSSL decrypt
    if (!function_exists('openssl_decrypt')) {
        return null;
    }

    // Truncate/pad key to appropriate length
    $keyLen = ($cipherVersion === 2) ? 32 : 16;
    $key = substr(str_pad((string) $key, $keyLen, "\0"), 0, $keyLen);

    $decrypted = openssl_decrypt(
        $encrypted,
        $method,
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $decrypted !== false ? rtrim($decrypted, "\0") : null;
}

// ============================================================
// 1. Buscar credenciais (env vars > hardcoded defaults)
// ============================================================
$host     = getenv('ERP_SQL_HOST') ?: '';
$port     = (int) (getenv('ERP_SQL_PORT') ?: 1433);
$database = getenv('ERP_SQL_DATABASE') ?: '';
$username = getenv('ERP_SQL_USERNAME') ?: '';
$password = getenv('ERP_SQL_PASSWORD') ?: '';

// Se nao tem env vars, tenta ler do core_config_data via MySQL
if (empty($host)) {
    $envFile = dirname(__DIR__) . '/app/etc/env.php';
    if (file_exists($envFile)) {
        $env = include $envFile;

        // Tenta key 'erp' no env.php
        if (isset($env['erp']['host'])) {
            $host     = $env['erp']['host'];
            $port     = (int) ($env['erp']['port'] ?? 1433);
            $database = $env['erp']['database'] ?? '';
            $username = $env['erp']['username'] ?? '';
            $password = $env['erp']['password'] ?? '';
            echo "[INFO] Credenciais lidas de app/etc/env.php (key 'erp')\n\n";
        }

        // Se ainda vazio, tenta MySQL
        if (empty($host) && isset($env['db']['connection']['default'])) {
            $db = $env['db']['connection']['default'];
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

                $sql = "SELECT path, value FROM core_config_data WHERE path LIKE 'grupoawamotos_erp/connection/%'";
                $stmt = $mysql->query($sql);
                $configs = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = str_replace('grupoawamotos_erp/connection/', '', $row['path']);
                    $configs[$key] = $row['value'];
                }

                if (!empty($configs['host'])) {
                    $host     = $configs['host'];
                    $port     = (int) ($configs['port'] ?? 1433);
                    $database = $configs['database'] ?? '';
                    $username = $configs['username'] ?? '';

                    // Password pode estar encriptada pelo Magento
                    if (isset($configs['password'])) {
                        $rawPassword = $configs['password'];
                        $decrypted = magentoDecrypt($rawPassword, $env['crypt']['key'] ?? '');
                        if ($decrypted !== null) {
                            $password = $decrypted;
                            echo "  [INFO] Senha decriptada automaticamente\n";
                        } else {
                            $password = $rawPassword;
                        }
                    }

                    echo "[INFO] Credenciais lidas do MySQL (core_config_data)\n";
                    echo "  AVISO: Se a senha estiver encriptada, passe via env var ERP_SQL_PASSWORD\n\n";
                }

                $mysql = null;
            } catch (\PDOException $e) {
                echo "[AVISO] MySQL indisponivel: " . $e->getMessage() . "\n";
                echo "  Passe credenciais via variaveis de ambiente.\n\n";
            }
        }
    }
}

if (empty($host)) {
    echo "ERRO: Host ERP nao configurado!\n\n";
    echo "Use variaveis de ambiente:\n";
    echo "  ERP_SQL_HOST=191.168.x.x ERP_SQL_PORT=1433 ERP_SQL_DATABASE=NomeDB \\\n";
    echo "  ERP_SQL_USERNAME=usuario ERP_SQL_PASSWORD=senha \\\n";
    echo "  php scripts/test_erp_standalone.php\n\n";
    exit(1);
}

// ============================================================
// 2. Mostrar config (sem senha)
// ============================================================
echo "== CONFIGURACAO ==\n";
echo "  Host:     $host\n";
echo "  Porta:    $port\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
$pwParts = explode(':', $password);
$isEncrypted = count($pwParts) >= 3 && is_numeric($pwParts[0]);
if (empty($password)) {
    echo "  Senha:    (vazia!)\n";
} elseif ($isEncrypted) {
    echo "  Senha:    AINDA ENCRIPTADA! (decriptacao falhou)\n";
    echo "            Use: ERP_SQL_PASSWORD=senha_real php scripts/test_erp_standalone.php\n";
} else {
    echo "  Senha:    " . str_repeat('*', min(strlen($password), 8)) . " (" . strlen($password) . " chars)\n";
}
echo "\n";

// ============================================================
// 3. Drivers PDO
// ============================================================
echo "== DRIVERS PDO ==\n";
$pdoDrivers = PDO::getAvailableDrivers();
echo "  Todos: " . implode(', ', $pdoDrivers) . "\n";

$hasSqlsrv = in_array('sqlsrv', $pdoDrivers, true);
$hasDblib  = in_array('dblib', $pdoDrivers, true);
$hasOdbc   = in_array('odbc', $pdoDrivers, true);

echo "  sqlsrv: " . ($hasSqlsrv ? 'OK' : 'NAO DISPONIVEL') . "\n";
echo "  dblib:  " . ($hasDblib ? 'OK' : 'NAO DISPONIVEL') . "\n";
echo "  odbc:   " . ($hasOdbc ? 'OK' : 'NAO DISPONIVEL') . "\n\n";

if (!$hasSqlsrv && !$hasDblib && !$hasOdbc) {
    echo "ERRO CRITICO: Nenhum driver SQL Server instalado!\n";
    echo "  Ubuntu: sudo apt install php-sybase   (dblib/FreeTDS)\n";
    echo "  Ubuntu: sudo pecl install pdo_sqlsrv  (Microsoft)\n";
    exit(1);
}

// ============================================================
// 4. Teste TCP
// ============================================================
echo "== TESTE DE REDE (TCP) ==\n";
$errno = 0;
$errstr = '';
$sock = @fsockopen($host, $port, $errno, $errstr, 5);

if ($sock) {
    echo "  [OK] TCP $host:$port acessivel!\n\n";
    fclose($sock);
} else {
    echo "  [FALHA] TCP $host:$port inacessivel\n";
    echo "    Erro [$errno]: $errstr\n";
    echo "    -> Verifique: firewall, IP do servidor liberado, SQL Server ouvindo na porta\n\n";
}

// ============================================================
// 5. Diagnostico FreeTDS (se dblib disponivel)
// ============================================================
if ($hasDblib) {
    echo "== DIAGNOSTICO FreeTDS ==\n";

    // Versão do FreeTDS
    $tsqlVersion = @shell_exec('tsql -C 2>/dev/null');
    if ($tsqlVersion) {
        $lines = array_filter(array_map('trim', explode("\n", $tsqlVersion)));
        foreach ($lines as $line) {
            if (stripos($line, 'version') !== false || stripos($line, 'tds') !== false) {
                echo "  $line\n";
            }
        }
    } else {
        echo "  tsql nao encontrado (pacote freetds-bin)\n";
    }

    // freetds.conf
    $confPaths = ['/etc/freetds/freetds.conf', '/etc/freetds.conf', '/usr/local/etc/freetds.conf'];
    foreach ($confPaths as $path) {
        if (file_exists($path)) {
            echo "  Config: $path\n";
            $conf = file_get_contents($path);
            // Verificar TDS version global
            if (preg_match('/^\s*tds version\s*=\s*(.+)$/mi', $conf, $m)) {
                echo "  TDS version (global): " . trim($m[1]) . "\n";
            } else {
                echo "  TDS version (global): NAO DEFINIDA (pode causar falha!)\n";
                echo "  -> Adicione 'tds version = 7.4' na secao [global] de $path\n";
            }
            break;
        }
    }
    echo "\n";
}

// ============================================================
// 6. Conexao PDO SQL Server
// ============================================================
echo "== TESTE DE CONEXAO ==\n";

$pdo = null;
$driverUsed = '';

// Tenta cada driver disponivel
$driversToTry = [];
if ($hasDblib)  { $driversToTry[] = 'dblib'; }
if ($hasSqlsrv) { $driversToTry[] = 'sqlsrv'; }
if ($hasOdbc)   { $driversToTry[] = 'odbc'; }

foreach ($driversToTry as $driver) {
    echo "  Tentando driver: $driver... ";

    try {
        switch ($driver) {
            case 'sqlsrv':
                $dsn = "sqlsrv:Server=$host,$port;Database=$database;TrustServerCertificate=1;LoginTimeout=10";
                break;
            case 'dblib':
                $dsn = "dblib:host=$host:$port;dbname=$database;version=7.4;charset=UTF-8";
                break;
            case 'odbc':
                $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$host,$port;Database=$database;TrustServerCertificate=yes";
                break;
            default:
                continue 2;
        }

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        $driverUsed = $driver;
        echo "OK!\n";
        break;
    } catch (\PDOException $e) {
        echo "FALHA\n";
        echo "    Erro: " . $e->getMessage() . "\n";
    }
}

if ($pdo === null) {
    // Fallback 1: tenta dblib com diferentes TDS versions via env var
    if ($hasDblib) {
        echo "\n  Tentando fallback com variaveis de ambiente TDSVER...\n";
        $tdsVersions = ['7.4', '7.3', '7.2', '7.1', '7.0', '8.0'];
        foreach ($tdsVersions as $tdsVer) {
            echo "  Tentando TDSVER=$tdsVer (porta $port)... ";
            putenv("TDSVER=$tdsVer");
            try {
                $dsn = "dblib:host=$host:$port;dbname=$database;charset=UTF-8";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 15,
                ]);
                $driverUsed = "dblib (TDSVER=$tdsVer)";
                echo "OK!\n";
                break;
            } catch (\PDOException $e) {
                echo "FALHA\n";
            }
        }
    }
}

if ($pdo === null && $hasDblib) {
    // Fallback 2: tenta usar aliases do FreeTDS (freetds.conf)
    echo "\n  Tentando aliases FreeTDS (freetds.conf)...\n";
    $aliases = ['ERPLOCAL', 'ERPLOCAL_OFF', 'ERPLOCAL_ENC', 'ERPLOCAL_73', 'ERPLOCAL_72'];
    foreach ($aliases as $alias) {
        echo "  Tentando alias [$alias]... ";
        try {
            // Quando usa alias, nao precisa porta - FreeTDS resolve
            $dsn = "dblib:host=$alias;dbname=$database;charset=UTF-8";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 15,
            ]);
            $driverUsed = "dblib (alias $alias)";
            echo "OK!\n";
            break;
        } catch (\PDOException $e) {
            echo "FALHA - " . substr($e->getMessage(), 0, 60) . "\n";
        }
    }
}

if ($pdo === null && $hasDblib && $port !== 3387) {
    // Fallback 3: tenta porta 3387 (porta padrao do ERP Sectra)
    echo "\n  A porta configurada ($port) pode estar errada.\n";
    echo "  Tentando porta 3387 (encontrada no freetds.conf)...\n";
    $altPorts = [3387];
    foreach ($altPorts as $altPort) {
        // Testa TCP primeiro
        $altSock = @fsockopen($host, $altPort, $errno, $errstr, 3);
        if ($altSock) {
            fclose($altSock);
            echo "  [OK] TCP $host:$altPort acessivel\n";
        } else {
            echo "  [FALHA] TCP $host:$altPort inacessivel\n";
            continue;
        }

        $tdsVersions = ['7.4', '7.3', '7.2', '7.0'];
        foreach ($tdsVersions as $tdsVer) {
            echo "  Tentando porta $altPort + TDS $tdsVer... ";
            putenv("TDSVER=$tdsVer");
            try {
                $dsn = "dblib:host=$host:$altPort;dbname=$database;charset=UTF-8";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 15,
                ]);
                $driverUsed = "dblib (porta $altPort, TDSVER=$tdsVer)";
                echo "OK!\n";
                echo "\n  *** ATENCAO: A porta correta e $altPort, nao $port! ***\n";
                echo "  *** Atualize no Admin > Stores > Config > AWA > ERP > Porta ***\n\n";
                break 2;
            } catch (\PDOException $e) {
                echo "FALHA\n";
            }
        }
    }
}

if ($pdo === null) {
    echo "\n  ERRO: Nao foi possivel conectar com nenhum driver/porta!\n";
    echo "  Verifique: host, porta, credenciais, firewall\n\n";
    echo "  DICA 1: A porta pode estar errada. Verificar freetds.conf:\n";
    echo "    cat /etc/freetds/freetds.conf\n\n";
    echo "  DICA 2: Tente conectar manualmente com tsql:\n";
    echo "    tsql -S ERPLOCAL -U $username -P senha\n\n";
    echo "  DICA 3: Se nada funcionar, instale o driver Microsoft:\n";
    echo "    sudo pecl install pdo_sqlsrv sqlsrv\n";
    exit(1);
}

echo "  [CONECTADO] Driver: $driverUsed\n\n";

// Info do servidor
try {
    $row = $pdo->query("SELECT @@VERSION AS v, @@SERVERNAME AS s, DB_NAME() AS d, GETDATE() AS t")->fetch();
    echo "  Servidor:  " . ($row['s'] ?? '?') . "\n";
    echo "  Database:  " . ($row['d'] ?? '?') . "\n";
    echo "  Hora:      " . ($row['t'] ?? '?') . "\n";
    echo "  Versao:    " . substr(($row['v'] ?? '?'), 0, 80) . "\n\n";
} catch (\Exception $e) {
    echo "  [AVISO] Nao conseguiu ler info do servidor: " . $e->getMessage() . "\n\n";
}

// ============================================================
// 6. Tabelas
// ============================================================
echo "== TABELAS DO ERP ==\n";
$tables = [
    'MT_MATERIAL'             => 'Produtos (SKU, nome, NCM)',
    'MT_ESTOQUEMEDIA'         => 'Estoque por filial',
    'MT_MATERIALLISTA'        => 'Listas de preco',
    'MT_MATERIALCUSTO'        => 'Custo dos produtos',
    'MT_GRUPOCOMERCIAL'       => 'Categorias / grupos',
    'MT_COMPOSICAOPRECO'      => 'Composicao de preco',
    'FN_FORNECEDORES'         => 'Clientes e fornecedores',
    'FN_CONTATO'              => 'Contatos (email, fone)',
    'VE_PEDIDO'               => 'Pedidos (cabecalho)',
    'VE_PEDIDOITENS'          => 'Itens dos pedidos',
    'VE_FATORPRECO'           => 'Listas de preco config',
    'GR_INTEGRACAOVALIDADOR'  => 'Integracao B2B',
    'CD_FILIAL'               => 'Filiais',
    'CL_TRANSPORTADORA'       => 'Transportadoras',
];

$ok = 0;
$fail = 0;

foreach ($tables as $table => $desc) {
    try {
        $stmt = $pdo->query("SELECT TOP 1 * FROM $table");
        $row = $stmt->fetch();
        $cols = $row ? count($row) : 0;
        echo "  [OK] $table ($desc) -- $cols colunas\n";
        $ok++;
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'permission') !== false || stripos($msg, 'denied') !== false) {
            echo "  [BLOQUEADO] $table ($desc) -- sem permissao SELECT\n";
        } elseif (stripos($msg, 'Invalid object') !== false) {
            echo "  [INEXISTENTE] $table ($desc) -- tabela nao existe\n";
        } else {
            echo "  [ERRO] $table ($desc) -- " . substr($msg, 0, 80) . "\n";
        }
        $fail++;
    }
}

echo "\n  Resultado: $ok acessiveis, $fail com problema\n\n";

// ============================================================
// 7. Escrita (GR_INTEGRACAOVALIDADOR)
// ============================================================
echo "== TESTE DE ESCRITA ==\n";
try {
    $testKey = 'MAGENTO_TEST_' . time();
    $pdo->prepare("INSERT INTO GR_INTEGRACAOVALIDADOR (INTEGRACAOORIGEM, CHAVE, VALIDADOR, CHAVEEXTERNA) VALUES ('TEST', ?, 'TEST', 'TEST')")
        ->execute([$testKey]);
    $pdo->prepare("DELETE FROM GR_INTEGRACAOVALIDADOR WHERE CHAVE = ? AND INTEGRACAOORIGEM = 'TEST'")
        ->execute([$testKey]);
    echo "  [OK] INSERT + DELETE permitido\n\n";
} catch (\PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'permission') !== false || stripos($msg, 'denied') !== false) {
        echo "  [SOMENTE LEITURA] Sem permissao de escrita (modo PULL sera usado)\n\n";
    } else {
        echo "  [AVISO] " . substr($msg, 0, 100) . "\n\n";
    }
}

// ============================================================
// 8. Volumetria
// ============================================================
echo "== VOLUMETRIA ==\n";
$counts = [
    'Produtos ativos'          => "SELECT COUNT(*) AS c FROM MT_MATERIAL WHERE CCKATIVO = 'S'",
    'Produtos comercializaveis' => "SELECT COUNT(*) AS c FROM MT_MATERIAL WHERE CCKATIVO = 'S' AND CKCOMERCIALIZA = 'S'",
    'Estoque (registros)'      => "SELECT COUNT(*) AS c FROM MT_ESTOQUEMEDIA",
    'Clientes'                 => "SELECT COUNT(*) AS c FROM FN_FORNECEDORES WHERE CKCLIENTE = 'S'",
    'Pedidos'                  => "SELECT COUNT(*) AS c FROM VE_PEDIDO",
    'Categorias'               => "SELECT COUNT(*) AS c FROM MT_GRUPOCOMERCIAL",
    'Listas de preco'          => "SELECT COUNT(*) AS c FROM VE_FATORPRECO",
    'Precos em listas'         => "SELECT COUNT(*) AS c FROM MT_MATERIALLISTA",
];

foreach ($counts as $label => $sql) {
    try {
        $row = $pdo->query($sql)->fetch();
        $n = $row ? (int) $row['c'] : 0;
        echo "  $label: " . number_format($n, 0, ',', '.') . "\n";
    } catch (\Exception $e) {
        echo "  $label: ERRO\n";
    }
}

// ============================================================
// 9. Amostra de dados
// ============================================================
echo "\n== AMOSTRA DE PRODUTOS (5 primeiros) ==\n";
try {
    $stmt = $pdo->query("SELECT TOP 5 CODIGO, DESCRICAO, CKCOMERCIALIZA, CCKATIVO FROM MT_MATERIAL WHERE CCKATIVO = 'S' ORDER BY CODIGO");
    while ($row = $stmt->fetch()) {
        echo sprintf("  SKU: %-20s | Comercializa: %s | %s\n",
            $row['CODIGO'],
            $row['CKCOMERCIALIZA'] ?? '?',
            mb_substr($row['DESCRICAO'] ?? '', 0, 50)
        );
    }
} catch (\Exception $e) {
    echo "  ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
}

echo "\n== AMOSTRA DE CLIENTES (5 primeiros) ==\n";
try {
    $stmt = $pdo->query("SELECT TOP 5 f.CODIGO, f.RAZAO, f.CGC, f.CPF, c.EMAIL
                         FROM FN_FORNECEDORES f
                         LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                         WHERE f.CKCLIENTE = 'S'
                         ORDER BY f.CODIGO DESC");
    while ($row = $stmt->fetch()) {
        $doc = !empty($row['CGC']) ? $row['CGC'] : ($row['CPF'] ?? '');
        echo sprintf("  Cod: %-6s | Doc: %-18s | %s | %s\n",
            $row['CODIGO'],
            $doc,
            mb_substr($row['RAZAO'] ?? '', 0, 35),
            $row['EMAIL'] ?? ''
        );
    }
} catch (\Exception $e) {
    echo "  ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
}

echo "\n== ESTOQUE FILIAL (amostra) ==\n";
try {
    $stmt = $pdo->query("SELECT TOP 5 FILIAL, COUNT(*) AS skus, SUM(QTDE) AS total_qtde
                         FROM MT_ESTOQUEMEDIA
                         WHERE QTDE > 0
                         GROUP BY FILIAL
                         ORDER BY SUM(QTDE) DESC");
    while ($row = $stmt->fetch()) {
        echo sprintf("  Filial %s: %s SKUs, %.0f unidades\n",
            $row['FILIAL'],
            number_format((int) $row['skus'], 0, ',', '.'),
            (float) $row['total_qtde']
        );
    }
} catch (\Exception $e) {
    echo "  ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
}

echo "\n====================================================\n";
echo "  TESTE CONCLUIDO\n";
echo "====================================================\n";

<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

/**
 * SQL Server Connection Class with Multiple Driver Support
 *
 * Supports:
 * - sqlsrv: Microsoft PHP Driver for SQL Server (recommended)
 * - dblib: FreeTDS driver (Linux)
 * - odbc: ODBC Driver
 */
class Connection implements ConnectionInterface
{
    private const DRIVER_SQLSRV = 'sqlsrv';
    private const DRIVER_DBLIB = 'dblib';
    private const DRIVER_ODBC = 'odbc';

    private Helper $helper;
    private LoggerInterface $logger;
    private ?\PDO $connection = null;
    private array $availableDrivers = [];

    public function __construct(
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->detectAvailableDrivers();
    }

    /**
     * Detect available PDO drivers for SQL Server
     */
    private function detectAvailableDrivers(): void
    {
        $pdoDrivers = \PDO::getAvailableDrivers();

        if (in_array('sqlsrv', $pdoDrivers)) {
            $this->availableDrivers[] = self::DRIVER_SQLSRV;
        }
        if (in_array('dblib', $pdoDrivers)) {
            $this->availableDrivers[] = self::DRIVER_DBLIB;
        }
        if (in_array('odbc', $pdoDrivers)) {
            $this->availableDrivers[] = self::DRIVER_ODBC;
        }
    }

    /**
     * Get available drivers
     */
    public function getAvailableDrivers(): array
    {
        return $this->availableDrivers;
    }

    /**
     * Check if any SQL Server driver is available
     */
    public function hasAvailableDriver(): bool
    {
        return !empty($this->availableDrivers);
    }

    /**
     * Get connection using the best available driver
     */
    public function getConnection(): \PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if (!$this->hasAvailableDriver()) {
            throw new \RuntimeException(
                'Nenhum driver SQL Server disponível. ' .
                'Instale: php-sqlsrv (Microsoft), php-sybase (dblib/FreeTDS), ou configure ODBC.'
            );
        }

        $host = $this->helper->getHost();
        $port = $this->helper->getPort();
        $database = $this->helper->getDatabase();
        $username = $this->helper->getUsername();
        $password = $this->helper->getPassword();

        // Get preferred driver from config or use auto-detection
        $preferredDriver = $this->helper->getDriver();
        $driversToTry = $this->getDriversToTry($preferredDriver);

        $lastException = null;
        foreach ($driversToTry as $driver) {
            try {
                $this->connection = $this->connectWithDriver(
                    $driver, $host, $port, $database, $username, $password
                );
                $this->logger->info(sprintf(
                    '[ERP] Connected to SQL Server using %s driver: %s:%d/%s',
                    $driver, $host, $port, $database
                ));
                return $this->connection;
            } catch (\PDOException $e) {
                $lastException = $e;
                $this->logger->warning(sprintf(
                    '[ERP] Failed to connect with %s driver: %s',
                    $driver, $e->getMessage()
                ));
            }
        }

        $this->logger->error('[ERP] All connection attempts failed');
        throw $lastException ?? new \RuntimeException('Connection failed with all available drivers');
    }

    /**
     * Get list of drivers to try, with preferred driver first
     */
    private function getDriversToTry(string $preferredDriver): array
    {
        if ($preferredDriver === 'auto' || empty($preferredDriver)) {
            // Default priority: sqlsrv > dblib > odbc
            return $this->availableDrivers;
        }

        if (in_array($preferredDriver, $this->availableDrivers)) {
            return [$preferredDriver];
        }

        // Fallback to auto if preferred driver not available
        return $this->availableDrivers;
    }

    /**
     * Create PDO connection with specific driver
     */
    private function connectWithDriver(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ): \PDO {
        $dsn = $this->buildDsn($driver, $host, $port, $database);
        $options = $this->getConnectionOptions($driver);

        $pdo = new \PDO($dsn, $username, $password, $options);

        // Set database if not already in DSN
        if ($database && $driver === self::DRIVER_DBLIB) {
            $pdo->exec('USE ' . $this->sanitizeIdentifier($database));
        }

        return $pdo;
    }

    /**
     * Build DSN string for specific driver
     */
    private function buildDsn(string $driver, string $host, int $port, string $database): string
    {
        switch ($driver) {
            case self::DRIVER_SQLSRV:
                // Microsoft SQL Server Driver for PHP
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%d;Database=%s;TrustServerCertificate=1;Encrypt=0',
                    $host, $port, $database
                );
                break;

            case self::DRIVER_DBLIB:
                // FreeTDS (dblib)
                $dsn = sprintf(
                    'dblib:host=%s:%d;charset=UTF-8',
                    $host, $port
                );
                // Database set after connection
                break;

            case self::DRIVER_ODBC:
                // ODBC Driver 17/18 for SQL Server
                $odbcDriver = $this->getOdbcDriverName();
                $dsn = sprintf(
                    'odbc:Driver={%s};Server=%s,%d;Database=%s;TrustServerCertificate=yes;',
                    $odbcDriver, $host, $port, $database
                );
                break;

            default:
                throw new \InvalidArgumentException('Unsupported driver: ' . $driver);
        }

        return $dsn;
    }

    /**
     * Detect ODBC driver name
     */
    private function getOdbcDriverName(): string
    {
        // Check for common ODBC driver names
        $possibleDrivers = [
            'ODBC Driver 18 for SQL Server',
            'ODBC Driver 17 for SQL Server',
            'ODBC Driver 13 for SQL Server',
            'FreeTDS',
            'SQL Server',
        ];

        // Try to detect from system
        $odbcIniFiles = ['/etc/odbcinst.ini', '/usr/local/etc/odbcinst.ini'];
        foreach ($odbcIniFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                foreach ($possibleDrivers as $driver) {
                    if (stripos($content, "[$driver]") !== false) {
                        return $driver;
                    }
                }
            }
        }

        // Default to ODBC Driver 17
        return 'ODBC Driver 17 for SQL Server';
    }

    /**
     * Get PDO connection options
     */
    private function getConnectionOptions(string $driver): array
    {
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        // Driver-specific options
        if ($driver === self::DRIVER_SQLSRV) {
            $options[\PDO::SQLSRV_ATTR_DIRECT_QUERY] = true;
            $options[\PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = true;
        }

        return $options;
    }

    /**
     * Test connection and return diagnostic info
     */
    public function testConnection(): array
    {
        // First check driver availability
        if (!$this->hasAvailableDriver()) {
            return [
                'success' => false,
                'message' => 'Nenhum driver SQL Server disponível.',
                'available_drivers' => [],
                'required_extensions' => [
                    'php-sqlsrv' => 'Driver Microsoft (recomendado)',
                    'php-sybase' => 'Driver FreeTDS (dblib)',
                    'php-odbc' => 'Driver ODBC'
                ],
                'installation_tips' => $this->getInstallationTips()
            ];
        }

        try {
            $pdo = $this->getConnection();

            // Get SQL Server version and info
            $stmt = $pdo->query('SELECT @@VERSION AS version, DB_NAME() AS db_name, GETDATE() AS server_time, @@SERVERNAME AS server_name');
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Count tables
            $stmt2 = $pdo->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
            $tableCount = $stmt2->fetch(\PDO::FETCH_ASSOC);

            // Get some table names as sample
            $stmt3 = $pdo->query("SELECT TOP 10 TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            $tables = $stmt3->fetchAll(\PDO::FETCH_COLUMN);

            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'driver_used' => $this->getActiveDriverName(),
                'available_drivers' => $this->availableDrivers,
                'server_name' => $row['server_name'] ?? 'Unknown',
                'version' => $this->parseVersion($row['version'] ?? ''),
                'full_version' => $row['version'] ?? 'Unknown',
                'database' => $row['db_name'] ?? 'Unknown',
                'server_time' => $row['server_time'] ?? 'Unknown',
                'table_count' => (int) ($tableCount['cnt'] ?? 0),
                'sample_tables' => $tables,
                'connection_string' => $this->getMaskedConnectionString()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha na conexão: ' . $e->getMessage(),
                'available_drivers' => $this->availableDrivers,
                'error_code' => $e->getCode(),
                'troubleshooting' => $this->getTroubleshootingTips($e)
            ];
        }
    }

    /**
     * Get active driver name
     */
    private function getActiveDriverName(): string
    {
        if ($this->connection === null) {
            return 'none';
        }
        return $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Parse SQL Server version string
     */
    private function parseVersion(string $fullVersion): string
    {
        if (preg_match('/Microsoft SQL Server (\d+)/', $fullVersion, $matches)) {
            $year = $matches[1];
            $editions = [
                '2022' => 'SQL Server 2022',
                '2019' => 'SQL Server 2019',
                '2017' => 'SQL Server 2017',
                '2016' => 'SQL Server 2016',
                '2014' => 'SQL Server 2014',
                '2012' => 'SQL Server 2012',
            ];
            return $editions[$year] ?? "SQL Server $year";
        }
        return 'SQL Server';
    }

    /**
     * Get masked connection string for display
     */
    private function getMaskedConnectionString(): string
    {
        $host = $this->helper->getHost();
        $port = $this->helper->getPort();
        $database = $this->helper->getDatabase();
        $username = $this->helper->getUsername();

        return sprintf('%s@%s:%d/%s', $username, $host, $port, $database);
    }

    /**
     * Get installation tips for SQL Server drivers
     */
    private function getInstallationTips(): array
    {
        return [
            'ubuntu_debian' => [
                'sqlsrv' => 'sudo apt-get install php-sqlsrv',
                'dblib' => 'sudo apt-get install php-sybase',
                'odbc' => 'sudo apt-get install php-odbc unixodbc-dev'
            ],
            'centos_rhel' => [
                'sqlsrv' => 'sudo yum install php-sqlsrv',
                'dblib' => 'sudo yum install php-mssql',
                'odbc' => 'sudo yum install php-odbc unixODBC'
            ],
            'pecl' => 'sudo pecl install sqlsrv pdo_sqlsrv',
            'docs' => 'https://docs.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac'
        ];
    }

    /**
     * Get troubleshooting tips based on error
     */
    private function getTroubleshootingTips(\Exception $e): array
    {
        $tips = [];
        $message = strtolower($e->getMessage());

        if (strpos($message, 'could not find driver') !== false) {
            $tips[] = 'Driver SQL Server não encontrado. Instale php-sqlsrv ou php-sybase.';
        }
        if (strpos($message, 'login failed') !== false || strpos($message, 'authentication') !== false) {
            $tips[] = 'Verifique usuário e senha.';
            $tips[] = 'Verifique se o usuário tem permissão para acessar o banco.';
        }
        if (strpos($message, 'timeout') !== false || strpos($message, 'connection') !== false) {
            $tips[] = 'Verifique se o servidor está acessível (ping).';
            $tips[] = 'Verifique se a porta 1433 está aberta no firewall.';
            $tips[] = 'Verifique se o SQL Server está configurado para aceitar conexões TCP/IP.';
        }
        if (strpos($message, 'certificate') !== false || strpos($message, 'ssl') !== false) {
            $tips[] = 'Problema de certificado SSL. TrustServerCertificate está habilitado.';
            $tips[] = 'Considere configurar certificado válido no SQL Server.';
        }
        if (strpos($message, 'database') !== false) {
            $tips[] = 'Verifique se o nome do banco de dados está correto.';
            $tips[] = 'Verifique se o usuário tem acesso ao banco especificado.';
        }

        if (empty($tips)) {
            $tips[] = 'Verifique os dados de conexão.';
            $tips[] = 'Consulte o log do SQL Server para mais detalhes.';
        }

        return $tips;
    }

    /**
     * Execute SELECT query
     */
    public function query(string $sql, array $params = []): array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute INSERT/UPDATE/DELETE
     */
    public function execute(string $sql, array $params = []): int
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Fetch single row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Fetch single column value
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Disconnect from database
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }

    /**
     * Sanitize identifier to prevent SQL injection
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }

    /**
     * Check if currently connected
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }
}

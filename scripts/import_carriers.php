<?php
/**
 * Script para importar transportadoras de arquivo CSV/Excel
 * 
 * Uso:
 *   php scripts/import_carriers.php --file=transportadoras.csv
 *   php scripts/import_carriers.php --file=transportadoras.csv --truncate
 * 
 * Formato esperado do CSV:
 *   nome,codigo,telefone,email,website,regioes,observacoes,ativo,ordem
 *   
 * Exemplo:
 *   "Correios PAC",correios_pac,"0800 725 7282",,https://correios.com.br,"SP,RJ,MG",,1,1
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

// Parse argumentos
$options = getopt('', ['file:', 'truncate', 'help']);

if (isset($options['help']) || !isset($options['file'])) {
    echo "
╔══════════════════════════════════════════════════════════════════╗
║           IMPORTADOR DE TRANSPORTADORAS                          ║
╠══════════════════════════════════════════════════════════════════╣
║ Uso:                                                             ║
║   php scripts/import_carriers.php --file=arquivo.csv             ║
║                                                                  ║
║ Opções:                                                          ║
║   --file=ARQUIVO    Caminho para o arquivo CSV                   ║
║   --truncate        Limpa tabela antes de importar               ║
║   --help            Mostra esta ajuda                            ║
║                                                                  ║
║ Formato do CSV (com cabeçalho):                                  ║
║   nome,codigo,telefone,email,website,regioes,observacoes,ativo   ║
║                                                                  ║
║ Exemplo:                                                         ║
║   \"Jadlog\",jadlog,\"11 3563-2000\",sac@jadlog.com.br,,\"BR\",\"Rápida\",1  ║
╚══════════════════════════════════════════════════════════════════╝
";
    exit(0);
}

$file = $options['file'];
$truncate = isset($options['truncate']);

if (!file_exists($file)) {
    echo "❌ Erro: Arquivo não encontrado: $file\n";
    exit(1);
}

echo "📦 Importando transportadoras de: $file\n";

try {
    /** @var \Magento\Framework\App\ResourceConnection $resource */
    $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
    $connection = $resource->getConnection();
    $tableName = $resource->getTableName('grupoawamotos_carriers');

    // Truncate se solicitado
    if ($truncate) {
        echo "🗑️  Limpando tabela...\n";
        $connection->truncateTable($tableName);
    }

    // Abrir arquivo
    $handle = fopen($file, 'r');
    if (!$handle) {
        throw new \Exception("Não foi possível abrir o arquivo");
    }

    // Ler cabeçalho
    $header = fgetcsv($handle, 0, ',', '"');
    if (!$header) {
        throw new \Exception("Arquivo CSV vazio ou inválido");
    }

    // Mapear colunas
    $columnMap = [
        'nome' => 'name',
        'name' => 'name',
        'codigo' => 'code',
        'code' => 'code',
        'telefone' => 'contact_phone',
        'phone' => 'contact_phone',
        'contact_phone' => 'contact_phone',
        'email' => 'contact_email',
        'contact_email' => 'contact_email',
        'website' => 'website',
        'site' => 'website',
        'regioes' => 'regions',
        'regions' => 'regions',
        'observacoes' => 'notes',
        'notes' => 'notes',
        'obs' => 'notes',
        'ativo' => 'is_active',
        'active' => 'is_active',
        'is_active' => 'is_active',
        'ordem' => 'sort_order',
        'order' => 'sort_order',
        'sort_order' => 'sort_order'
    ];

    // Normalizar cabeçalho
    $headerMap = [];
    foreach ($header as $idx => $col) {
        $col = strtolower(trim($col));
        $col = preg_replace('/[^a-z_]/', '', $col);
        if (isset($columnMap[$col])) {
            $headerMap[$idx] = $columnMap[$col];
        }
    }

    $imported = 0;
    $errors = 0;
    $lineNum = 1;

    while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
        $lineNum++;
        
        // Montar dados
        $data = [];
        foreach ($headerMap as $idx => $dbCol) {
            $value = isset($row[$idx]) ? trim($row[$idx]) : null;
            
            // Tratamento especial por coluna
            switch ($dbCol) {
                case 'code':
                    // Gerar código se não existir
                    if (empty($value) && isset($data['name'])) {
                        $value = preg_replace('/[^a-z0-9]/', '_', strtolower($data['name']));
                    }
                    break;
                case 'regions':
                    // Converter lista de regiões para JSON
                    if ($value) {
                        $regions = array_map('trim', explode(',', $value));
                        $value = json_encode($regions);
                    }
                    break;
                case 'is_active':
                    $value = in_array(strtolower($value), ['1', 'sim', 'yes', 'true', 'ativo', 's']) ? 1 : 0;
                    break;
                case 'sort_order':
                    $value = (int) $value;
                    break;
            }
            
            $data[$dbCol] = $value ?: null;
        }

        // Validar campos obrigatórios
        if (empty($data['name'])) {
            echo "⚠️  Linha $lineNum: Nome vazio, pulando...\n";
            $errors++;
            continue;
        }

        // Gerar código se necessário
        if (empty($data['code'])) {
            $data['code'] = preg_replace('/[^a-z0-9]/', '_', strtolower($data['name']));
            $data['code'] = preg_replace('/_+/', '_', $data['code']);
            $data['code'] = trim($data['code'], '_');
        }

        // Valores padrão
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $imported + 1;
        }

        try {
            // Insert or update
            $connection->insertOnDuplicate(
                $tableName,
                $data,
                ['name', 'contact_phone', 'contact_email', 'website', 'regions', 'notes', 'is_active', 'sort_order']
            );
            $imported++;
            echo "✅ Importado: {$data['name']} ({$data['code']})\n";
        } catch (\Exception $e) {
            echo "❌ Erro linha $lineNum: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    fclose($handle);

    echo "\n";
    echo "══════════════════════════════════════════\n";
    echo "📊 Resultado:\n";
    echo "   ✅ Importados: $imported\n";
    echo "   ❌ Erros: $errors\n";
    echo "══════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
    exit(1);
}

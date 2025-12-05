#!/usr/bin/env php
<?php
/**
 * Script para importar transportadoras do CSV para a tabela grupoawamotos_carriers
 * 
 * Uso: php scripts/import_transportadoras_csv.php [--truncate] [--file=arquivo.csv]
 * 
 * @author GrupoAwamotos
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'admin';
$params[\Magento\Store\Model\Store::CUSTOM_ENTRY_POINT_PARAM] = true;
$bootstrap = Bootstrap::create(BP, $params);

$objectManager = $bootstrap->getObjectManager();

// Parse command line arguments
$options = getopt('', [
    'truncate',            // apaga tabela antes
    'file:',               // caminho CSV
    'dry-run',             // não grava, só simula
    'limit:',              // limita número de linhas processadas
    'verbose',             // mostra cada linha
    'log-json:',           // grava saída detalhada em arquivo JSON
    'skip-invalid-cnpj',   // ignora linhas com CNPJ inválido
    'force-insert',        // força inserção mesmo se nome código já visto (para testes)
    'auto-sort',           // aplica heurística de sort_order por UF
    'normalize-regions',   // gera JSON estruturado no campo regions
]);
$truncate = isset($options['truncate']);
$dryRun   = isset($options['dry-run']);
$verbose  = isset($options['verbose']);
$limit    = isset($options['limit']) ? (int)$options['limit'] : 0;
$logJsonFile = $options['log-json'] ?? '';
$skipInvalidCnpj = isset($options['skip-invalid-cnpj']);
$forceInsert = isset($options['force-insert']);
$autoSort = isset($options['auto-sort']);
$normalizeRegions = isset($options['normalize-regions']);

$csvFile = $options['file'] ?? BP . '/pub/media/import/transportadora - sheet1.csv';

if (!file_exists($csvFile)) {
    echo "Erro: Arquivo CSV não encontrado: {$csvFile}\n";
    exit(1);
}

try {
    /** @var \Magento\Framework\App\ResourceConnection $resource */
    $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
    $connection = $resource->getConnection();
    $tableName = $resource->getTableName('grupoawamotos_carriers');
    
    // Verifica se a tabela existe
    if (!$connection->isTableExists($tableName)) {
        echo "Erro: Tabela {$tableName} não existe. Execute setup:upgrade primeiro.\n";
        exit(1);
    }
    
    // Truncate se solicitado
    if ($truncate) {
        $connection->truncateTable($tableName);
        echo "Tabela {$tableName} limpa.\n";
    }
    
    // Abre o arquivo CSV
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        echo "Erro: Não foi possível abrir o arquivo CSV.\n";
        exit(1);
    }
    
    // Lê o cabeçalho
    $header = fgetcsv($handle);
    echo "Cabeçalho CSV: " . implode(', ', $header) . "\n\n";
    
    // Mapeia os índices das colunas
    $colMap = [];
    foreach ($header as $idx => $col) {
        $colMap[strtolower(trim($col))] = $idx;
    }
    
    $imported = 0;
    $updated  = 0;
    $skipped  = 0;
    $errors   = 0;
    $duplicatesInRun = 0;
    $invalidCnpj = 0;
    $seenCodes = [];
    $logRows = [];
    $sequence = 0; // incremental para desempate em sort_order
    
    echo "Iniciando importação...\n";
    
    $processed = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if ($limit && $processed >= $limit) {
            echo "Limite de {$limit} linhas atingido. Encerrando loop.\n";
            break;
        }
        $processed++;
        // Ignora linhas vazias
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Função de validação de CNPJ
        $validateCnpj = function(string $cnpjRaw): bool {
            $digits = preg_replace('/[^0-9]/', '', $cnpjRaw);
            if (strlen($digits) !== 14) {
                return false;
            }
            // Elimina sequências repetidas
            if (preg_match('/^(\d)\1{13}$/', $digits)) {
                return false;
            }
            // Calcula DV1
            $sum = 0;
            $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
            for ($i=0; $i<12; $i++) { $sum += (int)$digits[$i] * $weights1[$i]; }
            $rest = $sum % 11; $dv1 = ($rest < 2) ? 0 : 11 - $rest;
            if ((int)$digits[12] !== $dv1) { return false; }
            // Calcula DV2
            $sum = 0;
            $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
            for ($i=0; $i<13; $i++) { $sum += (int)$digits[$i] * $weights2[$i]; }
            $rest = $sum % 11; $dv2 = ($rest < 2) ? 0 : 11 - $rest;
            return (int)$digits[13] === $dv2;
        };

        $razaoSocial = trim($row[$colMap['razão social'] ?? $colMap['razao social'] ?? 0] ?? '');
        $fantasia = trim($row[$colMap['fantasia'] ?? 1] ?? '');
        $cnpj = trim($row[$colMap['c.n.p.j.'] ?? $colMap['cnpj'] ?? 2] ?? '');
        $ie = trim($row[$colMap['i.e.'] ?? $colMap['ie'] ?? 3] ?? '');
        $endereco = trim($row[$colMap['endereço'] ?? $colMap['endereco'] ?? 4] ?? '');
        $complemento = trim($row[$colMap['complemento'] ?? 5] ?? '');
        $numero = trim($row[$colMap['número'] ?? $colMap['numero'] ?? 6] ?? '');
        $bairro = trim($row[$colMap['bairro'] ?? 7] ?? '');
        $cidade = trim($row[$colMap['cidade'] ?? 8] ?? '');
        $cep = trim($row[$colMap['cep'] ?? 9] ?? '');
        $uf = trim($row[$colMap['uf'] ?? 10] ?? '');
        
        // Ignora se não tiver razão social ou fantasia
        if (empty($razaoSocial) && empty($fantasia)) {
            $skipped++;
            continue;
        }
        
        // Usa fantasia como nome principal, ou razão social, com normalização
        $nomeBase = !empty($fantasia) ? $fantasia : $razaoSocial;
        $nome = strtoupper(trim(preg_replace('/\s+/', ' ', $nomeBase)));
        // Limita tamanho para evitar overflow visual
        if (strlen($nome) > 80) {
            $nome = substr($nome, 0, 77) . '...';
        }
        
        // Ignora transportadoras com nomes genéricos ou vazios
        $ignorar = ['', '.', 'SEDEX', 'PAC', 'CORREIO', 'PROPRIO', 'SANTA CRUZ'];
        if (in_array(strtoupper($nome), $ignorar)) {
            $skipped++;
            continue;
        }
        
        // Gera código único baseado no CNPJ ou nome
        $codigo = '';
        $cnpjValido = true;
        if (!empty($cnpj) && strtoupper($cnpj) !== 'ISENTO') {
            // Remove caracteres não numéricos
            $codigo = 'CNPJ_' . preg_replace('/[^0-9]/', '', $cnpj);
            $cnpjValido = $validateCnpj($cnpj);
            if (!$cnpjValido) {
                $invalidCnpj++;
                if ($skipInvalidCnpj) {
                    $skipped++;
                    if ($verbose) { echo "CNPJ inválido ignorado: {$cnpj} / {$nome}\n"; }
                    continue;
                }
            }
        } else {
            // Gera código a partir do nome
            $codigo = 'TRANS_' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nome), 0, 20));
        }
        
        // Monta endereço completo
        $enderecoCompleto = '';
        if (!empty($endereco)) {
            $enderecoCompleto = $endereco;
            if (!empty($numero) && $numero !== '.') {
                $enderecoCompleto .= ', ' . $numero;
            }
            if (!empty($complemento) && $complemento !== '.') {
                $enderecoCompleto .= ' - ' . $complemento;
            }
            if (!empty($bairro)) {
                $enderecoCompleto .= ' - ' . $bairro;
            }
            if (!empty($cidade)) {
                $enderecoCompleto .= ' - ' . $cidade;
            }
            if (!empty($uf)) {
                $enderecoCompleto .= '/' . $uf;
            }
            if (!empty($cep)) {
                $enderecoCompleto .= ' - CEP: ' . $cep;
            }
        }
        
        // Observações com dados da empresa
        $observacoes = "Razão Social: {$razaoSocial}";
        if (!empty($cnpj)) {
            $observacoes .= "\nCNPJ: {$cnpj}";
        }
        if (!empty($ie) && $ie !== 'ISENTO') {
            $observacoes .= "\nIE: {$ie}";
        }
        if (!empty($enderecoCompleto)) {
            $observacoes .= "\nEndereço: {$enderecoCompleto}";
        }
        
        // Determina regiões (mantém compatibilidade com formato antigo)
        $regioesRaw = 'Nacional';
        if (!empty($uf)) {
            $regioesRaw = strtoupper($uf);
        }
        $regionsValue = $regioesRaw; // padrão legacy
        if ($normalizeRegions) {
            $regionsValue = json_encode([
                'primary' => $regioesRaw,
                'all' => $regioesRaw === 'Nacional' ? ['BR'] : [$regioesRaw],
                'raw' => $regioesRaw
            ], JSON_UNESCAPED_UNICODE);
        }

        // Heurística de prioridade por UF (menor sort_order aparece primeiro)
        $sortOrder = 1000; // fallback alto
        if ($autoSort) {
            $ufPriority = [
                'SP' => 10,
                'RJ' => 20,
                'MG' => 30,
                'PR' => 40,
                'SC' => 50,
                'RS' => 60,
                'ES' => 70,
                'BA' => 80,
                'GO' => 90,
                'DF' => 100,
            ];
            $base = $ufPriority[$regioesRaw] ?? 200;
            // Adiciona sequência para preservar ordem relativa
            $sortOrder = $base + $sequence;
        }
        
        try {
            // Verifica se já existe pelo código
            $exists = $connection->fetchOne(
                "SELECT carrier_id FROM {$tableName} WHERE code = ?",
                [$codigo]
            );
            
            if (isset($seenCodes[$codigo]) && !$forceInsert) {
                // duplicado dentro do mesmo CSV
                $duplicatesInRun++;
                $skipped++;
                if ($verbose) {
                    echo "Duplicado em memória (run): {$codigo} / {$nome}\n";
                }
                continue;
            }
            $seenCodes[$codigo] = true;

            if ($exists && !$forceInsert) {
                // Atualiza
                if (!$dryRun) {
                    $connection->update($tableName, [
                        'name' => $nome,
                        'regions' => $regionsValue,
                        'notes' => $observacoes,
                        'is_active' => 1,
                        'sort_order' => $sortOrder,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], ['carrier_id = ?' => $exists]);
                }
                $updated++;
            } else {
                // Insere
                if (!$dryRun) {
                    $connection->insert($tableName, [
                        'name' => $nome,
                        'code' => $codigo,
                        'contact_phone' => '',
                        'contact_email' => '',
                        'website' => '',
                        'regions' => $regionsValue,
                        'notes' => $observacoes,
                        'is_active' => 1,
                        'sort_order' => $sortOrder,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $imported++;
            }
            $sequence++;
            if ($verbose) {
                echo ($exists ? 'Atualizado' : 'Inserido') . ": {$codigo} / {$nome}\n";
            }
            // Mostra progresso a cada 200 registros (somando inserções+atualizações)
            $progressCount = $imported + $updated;
            if ($progressCount > 0 && $progressCount % 200 === 0) {
                echo "Progresso: Inseridos={$imported} Atualizados={$updated} Skipped={$skipped} Erros={$errors}\n";
            }
            // Log JSON line
            if ($logJsonFile) {
                $logRows[] = [
                    'code' => $codigo,
                    'name' => $nome,
                    'action' => $exists ? ($dryRun ? 'would_update' : 'update') : ($dryRun ? 'would_insert' : 'insert'),
                    'regions' => $regionsValue,
                    'cnpj_raw' => $cnpj,
                    'cnpj_valid' => $cnpjValido,
                    'sort_order' => $sortOrder,
                    'auto_sort' => $autoSort,
                    'normalize_regions' => $normalizeRegions,
                ];
            }
            
        } catch (\Exception $e) {
            $errors++;
            echo "Erro ao importar '{$nome}': " . $e->getMessage() . "\n";
        }
    }
    
    fclose($handle);
    
    echo "\n=== Importação Concluída ===\n";
    echo "Transportadoras inseridas: {$imported}\n";
    echo "Transportadoras atualizadas: {$updated}\n";
    echo "Registros ignorados: {$skipped}\n";
    echo "Duplicados na mesma execução: {$duplicatesInRun}\n";
    echo "Erros: {$errors}\n";
    if ($dryRun) {
        echo "(dry-run) Nenhuma alteração gravada.\n";
    }
    
    // Conta total na tabela
    if (!$dryRun) {
        $total = $connection->fetchOne("SELECT COUNT(*) FROM {$tableName}");
        echo "Total de transportadoras na base: {$total}\n";
    } else {
        echo "Total na base não consultado (dry-run).\n";
    }

    // Grava JSON se solicitado
    if ($logJsonFile) {
        file_put_contents($logJsonFile, json_encode([
            'file' => $csvFile,
            'dry_run' => $dryRun,
            'inserted' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'duplicates_in_run' => $duplicatesInRun,
            'errors' => $errors,
            'rows' => $logRows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Log JSON gravado em: {$logJsonFile}\n";
    }
    
} catch (\Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

#!/usr/bin/env php
<?php
/**
 * Script para importar produtos via CSV no Magento 2
 * Usa a API de importação do Magento
 */

use Magento\Framework\App\Bootstrap;
use Magento\ImportExport\Model\Import;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         IMPORTAÇÃO DE PRODUTOS - MAGENTO 2                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$csvFile = __DIR__ . '/../_csv/catalog_product_reorganized.csv';

if (!file_exists($csvFile)) {
    echo "❌ ERRO: Arquivo CSV não encontrado: {$csvFile}\n\n";
    exit(1);
}

echo "📄 Arquivo CSV: {$csvFile}\n";
echo "📊 Tamanho: " . number_format(filesize($csvFile) / 1024, 2) . " KB\n";
echo "📝 Linhas: " . count(file($csvFile)) . "\n\n";

try {
    $import = $objectManager->create(\Magento\ImportExport\Model\Import::class);
    
    // Configurar importação
    $import->setData([
        'entity' => 'catalog_product',
        'behavior' => Import::BEHAVIOR_APPEND, // Adicionar/Atualizar
        Import::FIELD_NAME_VALIDATION_STRATEGY => \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS,
        Import::FIELD_NAME_ALLOWED_ERROR_COUNT => 100,
        Import::FIELD_FIELD_SEPARATOR => ',',
        Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => ',',
    ]);
    
    echo "🚀 Iniciando validação do CSV...\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    // Criar source para o CSV
    $source = $objectManager->create(
        \Magento\ImportExport\Model\Import\Source\Csv::class,
        [
            'file' => $csvFile,
            'directory' => $objectManager->create(\Magento\Framework\Filesystem\Directory\ReadFactory::class)->create(__DIR__ . '/../_csv')
        ]
    );
    
    // Validar
    $validationResult = $import->validateSource($source);
    
    if (!$validationResult) {
        echo "❌ Validação falhou!\n\n";
        
        $errors = $import->getErrorAggregator();
        if ($errors->getErrorsCount() > 0) {
            echo "Erros encontrados:\n";
            foreach ($errors->getAllErrors() as $error) {
                echo "  ⚠️  " . $error->getErrorMessage() . " (Linha: " . $error->getRowNumber() . ")\n";
            }
        }
        echo "\n";
        exit(1);
    }
    
    echo "✅ Validação concluída com sucesso!\n\n";
    
    echo "🔄 Iniciando importação...\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    // Importar
    $result = $import->importSource();
    
    if ($result) {
        echo "✅ Importação concluída!\n\n";
        
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                     ESTATÍSTICAS FINAIS                        ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "📊 Produtos criados:     " . $import->getCreatedItemsCount() . "\n";
        echo "📊 Produtos atualizados: " . $import->getUpdatedItemsCount() . "\n";
        echo "📊 Produtos deletados:   " . $import->getDeletedItemsCount() . "\n";
        
        $errors = $import->getErrorAggregator();
        if ($errors->getErrorsCount() > 0) {
            echo "⚠️  Erros:               " . $errors->getErrorsCount() . "\n";
            echo "\nPrimeiros erros:\n";
            $count = 0;
            foreach ($errors->getAllErrors() as $error) {
                if ($count++ >= 5) break;
                echo "  • " . $error->getErrorMessage() . " (Linha: " . $error->getRowNumber() . ")\n";
            }
        } else {
            echo "✅ Erros:                0\n";
        }
        
        echo "\n🎉 Importação concluída com sucesso!\n";
        echo "📋 Próximo passo: Reindexar e limpar cache\n\n";
        
        exit(0);
    } else {
        echo "❌ Falha na importação!\n\n";
        
        $errors = $import->getErrorAggregator();
        if ($errors->getErrorsCount() > 0) {
            echo "Erros:\n";
            foreach ($errors->getAllErrors() as $error) {
                echo "  ⚠️  " . $error->getErrorMessage() . " (Linha: " . $error->getRowNumber() . ")\n";
            }
        }
        echo "\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

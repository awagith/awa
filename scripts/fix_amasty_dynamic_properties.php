#!/usr/bin/env php
<?php
/**
 * Script para corrigir propriedades dinâmicas deprecadas no Amasty Paction
 * PHP 8.2+ não permite criação de propriedades dinâmicas sem declaração
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     CORREÇÃO DE PROPRIEDADES DINÂMICAS - AMASTY PACTION       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$basePath = __DIR__ . '/../app/code/Amasty/Paction/Model/Command';

// Arquivos com propriedades dinâmicas
$files = [
    'Addcategory.php',
    'Amdelete.php',
    'Appendtext.php',
    'Copyimg.php',
    'Copyoptions.php',
    'Copyrelate.php',
    'Modifyprice.php',
    'Relate.php',
    'Replacetext.php',
    'Unrelate.php'
];

$statistics = [
    'fixed' => 0,
    'skipped' => 0,
    'errors' => 0
];

foreach ($files as $file) {
    $filePath = $basePath . '/' . $file;
    
    if (!file_exists($filePath)) {
        echo "⚠️  Arquivo não encontrado: {$file}\n";
        $statistics['skipped']++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Verificar se já tem as propriedades declaradas
    if (preg_match('/protected\s+\$resource;/m', $content) && 
        preg_match('/protected\s+\$connection;/m', $content)) {
        echo "✓  Já corrigido: {$file}\n";
        $statistics['skipped']++;
        continue;
    }
    
    // Encontrar o último protected antes do construtor
    $pattern = '/(protected\s+\$\w+;)\s*\n\s*\n\s*(public\s+function\s+__construct)/s';
    
    if (preg_match($pattern, $content, $matches)) {
        $properties = "\n    /**\n     * @var \\Magento\\Framework\\App\\ResourceConnection\n     */\n    protected \$resource;\n\n    /**\n     * @var \\Magento\\Framework\\DB\\Adapter\\AdapterInterface\n     */\n    protected \$connection;\n";
        
        $replacement = $matches[1] . $properties . "\n    " . $matches[2];
        $content = preg_replace($pattern, $replacement, $content);
        
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content)) {
                echo "✅ Corrigido: {$file}\n";
                $statistics['fixed']++;
            } else {
                echo "❌ Erro ao salvar: {$file}\n";
                $statistics['errors']++;
            }
        } else {
            echo "⚠️  Nenhuma alteração necessária: {$file}\n";
            $statistics['skipped']++;
        }
    } else {
        echo "⚠️  Padrão não encontrado em: {$file}\n";
        $statistics['skipped']++;
    }
}

echo "\n─────────────────────────────────────────────────────────────────\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     ESTATÍSTICAS FINAIS                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Arquivos corrigidos:  {$statistics['fixed']}\n";
echo "✓  Já corrigidos/Pulados: {$statistics['skipped']}\n";
echo "❌ Erros:                 {$statistics['errors']}\n";
echo "📊 Total processado:     " . array_sum($statistics) . "\n\n";

if ($statistics['errors'] > 0) {
    echo "⚠️  Alguns erros ocorreram durante a correção.\n\n";
    exit(1);
}

echo "🎉 Todas as correções foram aplicadas!\n";
echo "📋 Execute: php bin/magento cache:flush\n\n";

exit(0);

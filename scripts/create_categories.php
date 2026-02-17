#!/usr/bin/env php
<?php
/**
 * Script para criar categorias automaticamente no Magento 2
 * Baseado na análise de reorganização de categorias
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

$categoryFactory = $objectManager->get(\Magento\Catalog\Model\CategoryFactory::class);
$categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         CRIAÇÃO AUTOMÁTICA DE CATEGORIAS - MAGENTO 2          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Estrutura de categorias baseada na análise
$categoryStructure = [
    'Categorias' => [
        'Adaptadores' => [],
        'Antenas Anti-Cerol' => [],
        'Bagageiros' => [
            'Cromados' => [],
            'Pretos' => []
        ],
        'Bauletos' => [
            'Acessórios Para Bau' => [],
            'Bauletos 29 L' => [],
            'Bauletos 34 L' => [],
            'Bauletos 41 L' => []
        ],
        'Blocos Oticos' => [],
        'Borrachas' => [],
        'Capas De Corrente' => [],
        'Carcaças' => [
            'Carcaça Do Farol' => [],
            'Carcaça Painel Inferior' => [],
            'Carcaça Painel Interna' => [],
            'Carcaça Painel Superior' => []
        ],
        'Cavaletes' => [],
        'Estribos' => [],
        'Guidões' => [
            'Barras De Guidão' => []
        ],
        'Lentes' => [
            'Lente Dos Piscas' => [],
            'Lentes De Freio' => []
        ],
        'Manoplas' => [],
        'Outros' => [],
        'Pedaleiras' => [],
        'Piscas' => [],
        'Retrovisores' => [
            'Arrow' => [],
            'Cromados' => [],
            'Mini' => [],
            'Originais' => []
        ],
        'Roldanas' => [],
        'Suportes' => [
            'Suporte De Placa' => []
        ]
    ]
];

$statistics = [
    'created' => 0,
    'existing' => 0,
    'errors' => 0
];

/**
 * Buscar categoria por nome e parent
 */
function findCategoryByName($categoryFactory, $name, $parentId) {
    $category = $categoryFactory->create();
    $collection = $category->getCollection()
        ->addAttributeToFilter('name', $name)
        ->addAttributeToFilter('parent_id', $parentId)
        ->setPageSize(1);
    
    return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
}

/**
 * Criar ou obter categoria
 */
function createOrGetCategory($categoryFactory, $categoryRepository, $storeManager, $name, $parentId, $level, &$statistics) {
    // Verificar se já existe
    $existingCategory = findCategoryByName($categoryFactory, $name, $parentId);
    
    if ($existingCategory) {
        echo str_repeat("  ", $level) . "✓ Categoria já existe: {$name} (ID: {$existingCategory->getId()})\n";
        $statistics['existing']++;
        return $existingCategory;
    }
    
    try {
        // Criar nova categoria
        $category = $categoryFactory->create();
        $category->setName($name);
        $category->setIsActive(true);
        $category->setParentId($parentId);
        $category->setStoreId($storeManager->getStore()->getId());
        $category->setPath($categoryRepository->get($parentId)->getPath());
        $category->setIncludeInMenu(true);
        
        $savedCategory = $categoryRepository->save($category);
        
        echo str_repeat("  ", $level) . "✅ Criada: {$name} (ID: {$savedCategory->getId()})\n";
        $statistics['created']++;
        
        return $savedCategory;
        
    } catch (\Exception $e) {
        echo str_repeat("  ", $level) . "❌ Erro ao criar {$name}: " . $e->getMessage() . "\n";
        $statistics['errors']++;
        return null;
    }
}

/**
 * Processar estrutura de categorias recursivamente
 */
function processCategories($categoryFactory, $categoryRepository, $storeManager, $structure, $parentId = 2, $level = 0, &$statistics) {
    foreach ($structure as $categoryName => $children) {
        $category = createOrGetCategory(
            $categoryFactory, 
            $categoryRepository, 
            $storeManager, 
            $categoryName, 
            $parentId, 
            $level,
            $statistics
        );
        
        if ($category && !empty($children)) {
            processCategories(
                $categoryFactory, 
                $categoryRepository, 
                $storeManager, 
                $children, 
                $category->getId(), 
                $level + 1,
                $statistics
            );
        }
    }
}

// Executar criação de categorias
echo "🚀 Iniciando criação de categorias...\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

try {
    processCategories(
        $categoryFactory, 
        $categoryRepository, 
        $storeManager, 
        $categoryStructure,
        2, // Root category ID (Default Category)
        0,
        $statistics
    );
    
    echo "\n─────────────────────────────────────────────────────────────────\n";
    echo "✅ Processamento concluído!\n\n";
    
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                     ESTATÍSTICAS FINAIS                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ Categorias criadas:        {$statistics['created']}\n";
    echo "✓  Categorias já existentes:  {$statistics['existing']}\n";
    echo "❌ Erros:                      {$statistics['errors']}\n";
    echo "📊 Total processado:          " . ($statistics['created'] + $statistics['existing'] + $statistics['errors']) . "\n\n";
    
    if ($statistics['errors'] > 0) {
        echo "⚠️  Alguns erros ocorreram durante a criação. Verifique as mensagens acima.\n\n";
        exit(1);
    }
    
    echo "🎉 Todas as categorias foram criadas com sucesso!\n";
    echo "📋 Próximo passo: Importar o CSV reorganizado\n\n";
    
    exit(0);
    
} catch (\Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

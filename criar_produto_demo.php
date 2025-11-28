<?php
/**
 * Criar Produto de Demonstração
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🛍️  Criando Produto de Demonstração\n";
echo "====================================\n\n";

$productFactory = $objectManager->get('Magento\Catalog\Model\ProductFactory');
$categoryLinkManagement = $objectManager->get('\Magento\Catalog\Api\CategoryLinkManagementInterface');
$storeManager = $objectManager->get('Magento\Store\Model\StoreManager');

// Buscar categoria "Eletrônicos"
$categoryFactory = $objectManager->get('Magento\Catalog\Model\CategoryFactory');
$category = $categoryFactory->create();
$category->getResource()->load($category, 'eletronicos', 'url_key');
$categoryId = $category->getId() ?: 2;

$produtos = [
    [
        'sku' => 'DEMO-NOTEBOOK-001',
        'name' => 'Notebook Dell Inspiron 15',
        'price' => 3499.90,
        'special_price' => 2999.90,
        'description' => 'Notebook Dell Inspiron 15 com Intel Core i5, 8GB RAM, 256GB SSD',
        'short_description' => 'Notebook Dell Inspiron 15 - Ideal para trabalho e estudos',
        'weight' => 2.5,
        'qty' => 50
    ],
    [
        'sku' => 'DEMO-MOUSE-001',
        'name' => 'Mouse Gamer RGB',
        'price' => 149.90,
        'special_price' => 99.90,
        'description' => 'Mouse Gamer com iluminação RGB, 7 botões programáveis, DPI ajustável',
        'short_description' => 'Mouse Gamer RGB - Precisão e estilo',
        'weight' => 0.3,
        'qty' => 100
    ],
    [
        'sku' => 'DEMO-TECLADO-001',
        'name' => 'Teclado Mecânico RGB',
        'price' => 399.90,
        'special_price' => null,
        'description' => 'Teclado Mecânico com switches blue, iluminação RGB personalizável',
        'short_description' => 'Teclado Mecânico RGB - Performance profissional',
        'weight' => 0.8,
        'qty' => 75
    ]
];

foreach ($produtos as $prodData) {
    try {
        $product = $productFactory->create();
        $product->load($prodData['sku'], 'sku');
        
        $now = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+30 days'));
        if ($product->getId()) {
            echo "   - Produto já existe: {$prodData['name']} (atualizando flags)\n";
            // Atualiza flags de destaque/novos
            $product->setFeatured(1);
            $product->setNewsFromDate($now);
            $product->setNewsToDate($to);
            // Garante estoque e website
            $product->setStockData([
                'use_config_manage_stock' => 1,
                'manage_stock' => 1,
                'is_in_stock' => 1,
                'qty' => max(1, (int)($prodData['qty'] ?? 10))
            ]);
            $product->setWebsiteIds([1]);
            $product->save();
            echo "     ✓ Atualizado: featured=1, período de novos e estoque.\n";
            continue;
        }
        
        $product->setSku($prodData['sku']);
        $product->setName($prodData['name']);
        $product->setAttributeSetId(4); // Default attribute set
        $product->setStatus(1); // Enabled
        $product->setVisibility(4); // Catalog, Search
        $product->setTypeId('simple');
        $product->setPrice($prodData['price']);
        
        if ($prodData['special_price']) {
            $product->setSpecialPrice($prodData['special_price']);
            $product->setSpecialFromDate($now);
            $product->setSpecialToDate($to);
        }
        
        $product->setDescription($prodData['description']);
        $product->setShortDescription($prodData['short_description']);
        $product->setWeight($prodData['weight']);
        $product->setTaxClassId(2); // Taxable Goods
        
        // Flags destaque/novos
        $product->setFeatured(1);
        $product->setNewsFromDate($now);
        $product->setNewsToDate($to);

        // Stock
        $product->setStockData([
            'use_config_manage_stock' => 1,
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'qty' => $prodData['qty']
        ]);
        
        // Categorias
        $product->setCategoryIds([$categoryId]);
        
        // Website
        $product->setWebsiteIds([1]);
        
        $product->save();
        
        echo "   ✓ Produto criado: {$prodData['name']} (SKU: {$prodData['sku']})\n";
        
    } catch (\Exception $e) {
        echo "   ✗ Erro ao criar {$prodData['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Produtos de demonstração criados!\n";
echo "====================================\n\n";
echo "📌 Acesse: Catalog > Products no Admin para ver os produtos\n";
echo "📌 Para adicionar imagens: edite cada produto no Admin\n\n";

<?php
/**
 * Script para importar produtos e categorias com imagens no Magento 2
 * 
 * Uso: php importar_produtos_imagens.php
 */

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

// Repositories e Factories
$categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$categoryFactory = $objectManager->get(\Magento\Catalog\Model\CategoryFactory::class);
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productFactory = $objectManager->get(\Magento\Catalog\Model\ProductFactory::class);
$stockRegistry = $objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
$filesystem = $objectManager->get(\Magento\Framework\Filesystem::class);
$mediaDirectory = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
$imageUploader = $objectManager->get(\Magento\Catalog\Model\Product\Media\Config::class);

echo "🚀 Iniciando importação de produtos e categorias com imagens...\n\n";

/**
 * Função para criar/atualizar categoria
 */
function createCategory($categoryFactory, $categoryRepository, $name, $urlKey, $parentId = 2) {
    try {
        // Verificar se categoria existe
        $category = $categoryFactory->create();
        $collection = $category->getCollection()
            ->addAttributeToFilter('name', $name)
            ->setPageSize(1);
        
        if ($collection->getSize() > 0) {
            echo "✓ Categoria '{$name}' já existe\n";
            return $collection->getFirstItem()->getId();
        }
        
        // Criar nova categoria
        $category = $categoryFactory->create();
        $category->setName($name);
        $category->setIsActive(true);
        $category->setParentId($parentId);
        $category->setStoreId(0);
        $category->setUrlKey($urlKey);
        $category->setData('display_mode', 'PRODUCTS');
        $category->setData('is_anchor', 1);
        
        $category = $categoryRepository->save($category);
        echo "✅ Categoria '{$name}' criada com ID: {$category->getId()}\n";
        
        return $category->getId();
        
    } catch (\Exception $e) {
        echo "❌ Erro ao criar categoria '{$name}': " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Função para adicionar imagem ao produto
 */
function addImageToProduct($product, $imagePath, $isMain = true) {
    try {
        if (!file_exists($imagePath)) {
            echo "⚠️  Imagem não encontrada: {$imagePath}\n";
            return false;
        }
        
        // Adicionar imagem ao produto
        $product->addImageToMediaGallery(
            $imagePath,
            ['image', 'small_image', 'thumbnail'],
            false,
            false
        );
        
        return true;
        
    } catch (\Exception $e) {
        echo "❌ Erro ao adicionar imagem: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Função para criar produto
 */
function createProduct($productFactory, $productRepository, $stockRegistry, $data) {
    try {
        // Verificar se produto existe
        try {
            $existingProduct = $productRepository->get($data['sku']);
            echo "✓ Produto '{$data['sku']}' já existe\n";
            return $existingProduct;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Produto não existe, continuar criação
        }
        
        // Criar novo produto
        $product = $productFactory->create();
        $product->setSku($data['sku']);
        $product->setName($data['name']);
        $product->setAttributeSetId(4); // Default attribute set
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $product->setWeight($data['weight'] ?? 1);
        $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        $product->setTaxClassId(2); // Taxable Goods
        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $product->setPrice($data['price']);
        $product->setWebsiteIds([1]);
        $product->setCategoryIds($data['category_ids'] ?? []);
        $product->setStockData([
            'use_config_manage_stock' => 1,
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'qty' => $data['qty'] ?? 100
        ]);
        
        // Adicionar imagens se fornecidas
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $index => $imagePath) {
                addImageToProduct($product, $imagePath, $index === 0);
            }
        }
        
        $product = $productRepository->save($product);
        echo "✅ Produto '{$data['name']}' criado com SKU: {$data['sku']}\n";
        
        return $product;
        
    } catch (\Exception $e) {
        echo "❌ Erro ao criar produto '{$data['sku']}': " . $e->getMessage() . "\n";
        return null;
    }
}

// ============================================
// EXEMPLO DE USO
// ============================================

// 1. Criar Categorias
echo "\n📁 CRIANDO CATEGORIAS...\n";
echo "────────────────────────────────\n";

$categories = [
    [
        'name' => 'Roupas',
        'url_key' => 'roupas',
        'parent_id' => 2
    ],
    [
        'name' => 'Camisetas',
        'url_key' => 'camisetas',
        'parent_id' => 2
    ],
    [
        'name' => 'Calças',
        'url_key' => 'calcas',
        'parent_id' => 2
    ]
];

$categoryIds = [];
foreach ($categories as $cat) {
    $catId = createCategory(
        $categoryFactory,
        $categoryRepository,
        $cat['name'],
        $cat['url_key'],
        $cat['parent_id']
    );
    if ($catId) {
        $categoryIds[$cat['name']] = $catId;
    }
}

// 2. Criar Produtos com Imagens
echo "\n\n🛍️  CRIANDO PRODUTOS...\n";
echo "────────────────────────────────\n";

$products = [
    [
        'sku' => 'CAM-001',
        'name' => 'Camiseta Básica Branca',
        'price' => 49.90,
        'weight' => 0.2,
        'qty' => 50,
        'category_ids' => [$categoryIds['Camisetas'] ?? 2],
        'images' => [
            // Coloque suas imagens em pub/media/import/
            // BP . '/pub/media/import/camiseta-branca-1.jpg',
            // BP . '/pub/media/import/camiseta-branca-2.jpg',
        ]
    ],
    [
        'sku' => 'CAM-002',
        'name' => 'Camiseta Básica Preta',
        'price' => 49.90,
        'weight' => 0.2,
        'qty' => 75,
        'category_ids' => [$categoryIds['Camisetas'] ?? 2],
        'images' => [
            // BP . '/pub/media/import/camiseta-preta-1.jpg',
        ]
    ],
    [
        'sku' => 'CAL-001',
        'name' => 'Calça Jeans Azul',
        'price' => 129.90,
        'weight' => 0.5,
        'qty' => 30,
        'category_ids' => [$categoryIds['Calças'] ?? 2],
        'images' => [
            // BP . '/pub/media/import/calca-jeans-1.jpg',
            // BP . '/pub/media/import/calca-jeans-2.jpg',
        ]
    ]
];

foreach ($products as $productData) {
    createProduct(
        $productFactory,
        $productRepository,
        $stockRegistry,
        $productData
    );
}

echo "\n\n✨ Importação concluída!\n";
echo "────────────────────────────────\n";
echo "Limpe o cache para ver as mudanças:\n";
echo "php bin/magento cache:clean\n";
echo "php bin/magento cache:flush\n";
echo "php bin/magento indexer:reindex\n\n";

<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', [
    'entity::',       // product | category (default product)
    'limit::',        // limitar quantidade
    'dry-run',        // apenas simula
    'force',          // sobrescrever campos já preenchidos
    'skus-file::',    // caminho para arquivo de SKUs (apenas entity=product)
]);

$entity = isset($opts['entity']) ? strtolower((string)$opts['entity']) : 'product';
$limit  = isset($opts['limit']) ? (int)$opts['limit'] : 0;
$dryRun = array_key_exists('dry-run', $opts);
$force  = array_key_exists('force', $opts);
$skusFile = isset($opts['skus-file']) ? (string)$opts['skus-file'] : '';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

function clip(string $s, int $max): string {
    $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)) ?? '');
    if (mb_strlen($s, 'UTF-8') <= $max) return $s;
    $cut = mb_substr($s, 0, $max - 1, 'UTF-8');
    return rtrim($cut) . '…';
}

// Tratar como 'product' por padrão quando não for explicitamente 'category'
if ($entity !== 'category') {
    /** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
    $productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
    /** @var Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory */
    $productCollectionFactory = $om->get(Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);

    $collection = $productCollectionFactory->create();
    $collection->addAttributeToSelect(['name','short_description','description','meta_title','meta_description']);

    $skuFilter = [];
    if ($skusFile && is_file($skusFile)) {
        $lines = file($skusFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $l) { $skuFilter[] = trim($l); }
    }
    if ($skuFilter) {
        $collection->addAttributeToFilter('sku', ['in' => $skuFilter]);
        // limpar vazios sem usar arrow function (compatibilidade PHP < 7.4)
        $skuFilter = array_values(array_filter($skuFilter, function ($x) { return $x !== ''; }));
    }
    if ($limit > 0) { $collection->setPageSize($limit)->setCurPage(1); }

    $updated = 0; $skipped = 0; $errors = 0;
    foreach ($collection as $product) {
        try {
            $sku = (string)$product->getSku();
            $name = (string)$product->getName();
            $short = (string)$product->getData('short_description');
            $desc  = (string)$product->getData('description');
            $metaTitle = (string)$product->getData('meta_title');
            $metaDesc  = (string)$product->getData('meta_description');

            $newTitle = clip($name, 60);
            $baseDesc = $short !== '' ? $short : ($desc !== '' ? $desc : $name);
            $newDesc  = clip($baseDesc, 155);

            $changed = false;
            if ($force || $metaTitle === '' ) { $product->setData('meta_title', $newTitle); $changed = true; }
            if ($force || $metaDesc  === '' ) { $product->setData('meta_description', $newDesc); $changed = true; }

            if (!$changed) { $skipped++; continue; }
            if ($dryRun) { echo "[dry-run] SEO atualizado SKU $sku\n"; continue; }

            $productRepository->save($product);
            $updated++;
            echo "✓ SEO atualizado: $sku\n";
        } catch (\Throwable $e) {
            $errors++;
            echo "✗ Erro SEO SKU {$product->getSku()}: " . $e->getMessage() . "\n";
        }
    }
    echo "Resumo produtos: updated=$updated skipped=$skipped errors=$errors\n";
    exit($errors > 0 ? 1 : 0);
} elseif ($entity === 'category') {
    /** @var Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository */
    $categoryRepository = $om->get(Magento\Catalog\Api\CategoryRepositoryInterface::class);
    /** @var Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catColFactory */
    $catColFactory = $om->get(Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);

    $col = $catColFactory->create();
    $col->addAttributeToSelect(['name','meta_title','meta_description']);
    // excluir root categories 1 e 2
    $col->addAttributeToFilter('entity_id', ['nin' => [1,2]]);
    if ($limit > 0) { $col->setPageSize($limit)->setCurPage(1); }

    $updated = 0; $skipped = 0; $errors = 0;
    foreach ($col as $cat) {
        try {
            $name = (string)$cat->getName();
            $metaTitle = (string)$cat->getData('meta_title');
            $metaDesc  = (string)$cat->getData('meta_description');
            $newTitle = clip($name, 60);
            $newDesc  = clip("Confira $name e mais novidades.", 155);
            $changed = false;
            if ($force || $metaTitle === '') { $cat->setData('meta_title', $newTitle); $changed = true; }
            if ($force || $metaDesc  === '') { $cat->setData('meta_description', $newDesc); $changed = true; }
            if (!$changed) { $skipped++; continue; }
            if ($dryRun) { echo "[dry-run] SEO atualizado categoria {$cat->getId()}\n"; continue; }
            $categoryRepository->save($cat);
            $updated++;
            echo "✓ SEO atualizado categoria {$cat->getId()}\n";
        } catch (\Throwable $e) {
            $errors++;
            echo "✗ Erro SEO categoria {$cat->getId()}: " . $e->getMessage() . "\n";
        }
    }
    echo "Resumo categorias: updated=$updated skipped=$skipped errors=$errors\n";
    exit($errors > 0 ? 1 : 0);
} else {
    fwrite(STDERR, "Uso: php scripts/enrich_seo_meta.php [--entity product|category] [--limit N] [--dry-run] [--force] [--skus-file <path>]\n");
    exit(2);
}

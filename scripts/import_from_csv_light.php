<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

ini_set('display_errors', '1');
ini_set('memory_limit', '2048M');
set_time_limit(0);

$opts = getopt('', [
    'file:', 'delimiter::', 'enclosure::', 'escape::', 'dry-run'
]);

function need(array $o, string $k): string {
    if (empty($o[$k]) || !is_string($o[$k])) {
        fwrite(STDERR, "Uso: php scripts/import_from_csv_light.php --file <csv> [--dry-run]\n");
        exit(2);
    }
    return (string)$o[$k];
}

$file = need($opts, 'file');
$delimiter = isset($opts['delimiter']) && is_string($opts['delimiter']) ? $opts['delimiter'] : ',';
$enclosure = isset($opts['enclosure']) && is_string($opts['enclosure']) ? $opts['enclosure'] : '"';
$escape    = isset($opts['escape']) && is_string($opts['escape']) ? $opts['escape'] : '\\';
$dryRun = array_key_exists('dry-run', $opts);

if (!is_file($file)) {
    fwrite(STDERR, "ERRO: CSV não encontrado: $file\n");
    exit(3);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
/** @var Magento\Catalog\Model\ProductFactory $productFactory */
$productFactory = $om->get(Magento\Catalog\Model\ProductFactory::class);
/** @var Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry */
$stockRegistry = $om->get(Magento\CatalogInventory\Api\StockRegistryInterface::class);
/** @var Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $om->get(Magento\Catalog\Api\CategoryRepositoryInterface::class);
/** @var Magento\Catalog\Model\CategoryFactory $categoryFactory */
$categoryFactory = $om->get(Magento\Catalog\Model\CategoryFactory::class);
/** @var Magento\Eav\Model\Config $eavConfig */
$eavConfig = $om->get(Magento\Eav\Model\Config::class);

$rootCategoryId = 2; // default root
$importImageBase = BP . '/pub/media/import/';

function url_key_slug(string $v): string {
    $v = trim($v);
    $v = mb_strtolower($v, 'UTF-8');
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
    if ($t !== false) { $v = $t; }
    $v = preg_replace('/[^a-z0-9]+/i', '-', $v) ?? '';
    $v = preg_replace('/-+/', '-', $v) ?? '';
    return trim($v, '-');
}

function findCategoryIdByNameAndParent(
    Magento\Catalog\Model\CategoryFactory $categoryFactory,
    string $name,
    int $parentId
): ?int {
    $collection = $categoryFactory->create()->getCollection();
    $collection->addAttributeToFilter('name', $name)
        ->addAttributeToFilter('parent_id', $parentId)
        ->setPageSize(1);
    if ($collection->getSize() > 0) {
        return (int)$collection->getFirstItem()->getId();
    }
    return null;
}

function ensureCategoryPathChain(
    Magento\Catalog\Model\CategoryFactory $categoryFactory,
    Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
    string $path,
    int $rootId,
    bool $dryRun = false
): array {
    $parts = array_values(array_filter(array_map('trim', explode('/', $path)), function ($x) { return $x !== ''; }));
    $parent = $rootId;
    $chain = [];
    foreach ($parts as $part) {
        $existing = findCategoryIdByNameAndParent($categoryFactory, $part, $parent);
        if ($existing) { $parent = $existing; $chain[] = $parent; continue; }
        if ($dryRun) {
            echo "[dry-run] Criaria categoria: {$path} (nível: {$part})\n";
            // Não adiciona ID porque não existe ainda
            continue;
        }
        $cat = $categoryFactory->create();
        $cat->setName($part);
        $cat->setIsActive(true);
        $cat->setParentId($parent);
        $cat->setStoreId(0);
        $cat->setUrlKey(url_key_slug($part));
        $cat->setData('display_mode', 'PRODUCTS');
        $cat->setData('is_anchor', 1);
        $saved = $categoryRepository->save($cat);
        $parent = (int)$saved->getId();
        $chain[] = $parent;
        echo "✓ Categoria criada: {$path} (nível: {$part}, ID {$parent})\n";
    }
    return $chain; // IDs em ordem do primeiro nível após root até a folha
}

// Mapas utilitários
$visibilityMap = [
    'catalog, search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
    'catálogo, pesquisa' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
    'not visible individually' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
    'catalog' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
    'search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
];

function mapVisibility(?string $v): int {
    if (!$v) return \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
    $k = trim(mb_strtolower($v, 'UTF-8'));
    global $visibilityMap;
    return $visibilityMap[$k] ?? \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
}

// Atributo manufacturer (se existir)
$manufacturerAttr = $eavConfig->getAttribute('catalog_product', 'manufacturer');
$manufacturerExists = $manufacturerAttr && $manufacturerAttr->getId();

// Suporte a attribute_set_code
$entityType = $om->get(Magento\Eav\Model\Entity\Type::class)->loadByCode('catalog_product');
/** @var Magento\Eav\Api\AttributeSetRepositoryInterface $attrSetRepo */
$attrSetRepo = $om->get(Magento\Eav\Api\AttributeSetRepositoryInterface::class);
/** @var Magento\Framework\Api\SearchCriteriaBuilder $scb */
$scb = $om->get(Magento\Framework\Api\SearchCriteriaBuilder::class);
$searchCriteria = $scb->addFilter('entity_type_id', (int)$entityType->getEntityTypeId(), 'eq')->create();
$attrSetMap = [];
try {
    $attrSetList = $attrSetRepo->getList($searchCriteria);
    foreach ($attrSetList->getItems() as $set) {
        $attrSetMap[$set->getAttributeSetName()] = (int)$set->getAttributeSetId();
    }
} catch (\Throwable $e) {
    // fallback vazio; usaremos ID 4 (Default)
}

$fh = fopen($file, 'r');
if (!$fh) { fwrite(STDERR, "ERRO ao abrir $file\n"); exit(4); }
$header = fgetcsv($fh, 0, $delimiter, $enclosure, $escape);
if (!$header) { fwrite(STDERR, "ERRO: cabeçalho inválido\n"); exit(5); }
$col = array_flip($header);

// Índices
$ix = fn(string $name) => $col[$name] ?? null;
$iSku = $ix('sku');
$iSet = $ix('attribute_set_code');
$iType = $ix('product_type');
$iCats = $ix('categories');
$iWebs = $ix('product_websites');
$iName = $ix('name');
$iDesc = $ix('description');
$iShort= $ix('short_description');
$iPrice= $ix('price');
$iWeight=$ix('weight');
$iStatus=$ix('status');
$iVis  =$ix('visibility');
$iTax  =$ix('tax_class_name');
$iQty  =$ix('qty');
$iInSt =$ix('is_in_stock');
$iUrl  =$ix('url_key');
$iMfg  =$ix('manufacturer');
$iImg  =$ix('image');
$iSImg =$ix('small_image');
$iThmb =$ix('thumbnail');
$iAdd  =$ix('additional_images');

$created=0;$updated=0;$skipped=0;$errors=0;$rownum=1;
while (($row = fgetcsv($fh, 0, $delimiter, $enclosure, $escape)) !== false) {
    $rownum++;
    try {
        $sku = isset($row[$iSku]) ? trim((string)$row[$iSku]) : '';
        if ($sku === '') { $skipped++; continue; }
        $name = isset($row[$iName]) ? (string)$row[$iName] : $sku;
        $price = isset($row[$iPrice]) && $row[$iPrice] !== '' ? (float)$row[$iPrice] : 0.0;
        $weight= isset($row[$iWeight]) && $row[$iWeight] !== '' ? (float)$row[$iWeight] : 0.1;
        $status= isset($row[$iStatus]) ? (int)$row[$iStatus] : 1;
        $vis   = mapVisibility(isset($row[$iVis]) ? (string)$row[$iVis] : null);
        $qty   = isset($row[$iQty]) && $row[$iQty] !== '' ? (float)$row[$iQty] : 100;
        $instock = isset($row[$iInSt]) ? ((int)$row[$iInSt] ? 1 : 0) : 1;
        $urlKey = isset($row[$iUrl]) ? (string)$row[$iUrl] : '';
        $type  = isset($row[$iType]) ? (string)$row[$iType] : 'simple';
        $setCode = isset($row[$iSet]) ? (string)$row[$iSet] : 'Default';
        $setId = $attrSetMap[$setCode] ?? ($attrSetMap['Default'] ?? 4);

        // Categorias
        $categoryIds = [];
        if ($iCats !== null && isset($row[$iCats]) && trim((string)$row[$iCats]) !== '') {
            $paths = array_map('trim', explode('|', (string)$row[$iCats]));
            foreach ($paths as $path) {
                if ($path === '') continue;
                $chainIds = ensureCategoryPathChain($categoryFactory, $categoryRepository, $path, $rootCategoryId, $dryRun);
                foreach ($chainIds as $cid) { $categoryIds[] = $cid; }
            }
            $categoryIds = array_values(array_unique($categoryIds));
        }

        // Produto: criar ou atualizar
        $product = null; $isNew = false;
        try { $product = $productRepository->get($sku); }
        catch (Magento\Framework\Exception\NoSuchEntityException $e) { $isNew = true; }
        if ($isNew) { $product = $productFactory->create(); $product->setSku($sku); }

        $product->setName($name);
        $product->setAttributeSetId($setId);
        $product->setStatus($status);
        $product->setWeight($weight);
        $product->setVisibility($vis);
        $product->setTaxClassId(2); // Taxable Goods (padrão)
        $product->setTypeId($type);
        $product->setPrice($price);
        $product->setWebsiteIds([1]);
        if ($categoryIds) { $product->setCategoryIds($categoryIds); }
        if ($iDesc !== null && isset($row[$iDesc])) { $product->setDescription((string)$row[$iDesc]); }
        if ($iShort !== null && isset($row[$iShort])) { $product->setShortDescription((string)$row[$iShort]); }
        if ($urlKey !== '') { $product->setUrlKey($urlKey); }
        // manufacturer, se existir
        if ($manufacturerExists && $iMfg !== null && isset($row[$iMfg]) && trim((string)$row[$iMfg]) !== '') {
            $product->setData('manufacturer', (string)$row[$iMfg]);
        }

        // Estoque (modo simples)
        $product->setStockData([
            'use_config_manage_stock' => 1,
            'manage_stock' => 1,
            'is_in_stock' => $instock,
            'qty' => $qty,
        ]);

        // Imagens
        $roles = ['image' => $iImg, 'small_image' => $iSImg, 'thumbnail' => $iThmb];
        foreach ($roles as $role => $idx) {
            if ($idx !== null && isset($row[$idx]) && trim((string)$row[$idx]) !== '') {
                $rel = ltrim((string)$row[$idx], '/');
                $abs = $importImageBase . $rel;
                if (is_file($abs)) {
                    $product->addImageToMediaGallery($abs, [$role], false, false);
                }
            }
        }
        if ($iAdd !== null && isset($row[$iAdd]) && trim((string)$row[$iAdd]) !== '') {
            $parts = array_map('trim', explode(',', (string)$row[$iAdd]));
            foreach ($parts as $img) {
                if ($img === '') continue;
                $abs = $importImageBase . ltrim($img, '/');
                if (is_file($abs)) {
                    $product->addImageToMediaGallery($abs, null, false, false);
                }
            }
        }

        if ($dryRun) {
            echo "[dry-run] " . ($isNew ? 'Criaria' : 'Atualizaria') . " SKU $sku com categorias (" . implode(',', $categoryIds) . ")\n";
            continue;
        }

        $saved = $productRepository->save($product);
        if ($isNew) { $created++; } else { $updated++; }
        echo ($isNew ? '✓ Criado ' : '↻ Atualizado ') . $sku . "\n";
    } catch (\Throwable $e) {
        $errors++;
        echo "✗ Erro na linha $rownum: " . $e->getMessage() . "\n";
    }
}
fclose($fh);

echo "\nResumo: criados=$created, atualizados=$updated, pulados=$skipped, erros=$errors\n";
exit($errors > 0 ? 1 : 0);

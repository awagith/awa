<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', ['sku::', 'file::', 'dry-run::']);
$skuOpt = isset($opts['sku']) ? (string)$opts['sku'] : '';
$fileOpt = isset($opts['file']) ? (string)$opts['file'] : '';
$dryRun = isset($opts['dry-run']) ? (bool)$opts['dry-run'] : false;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
/** @var Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory */
$productCollectionFactory = $om->get(Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);

/** @var Magento\Catalog\Model\Product\Media\Config $mediaConfig */
$mediaConfig = $om->get(Magento\Catalog\Model\Product\Media\Config::class);

/**
 * Resolve list of SKUs to process
 */
$skus = [];
if ($skuOpt !== '') {
    $skus = array_filter(array_map('trim', explode(',', $skuOpt)));
} elseif ($fileOpt !== '') {
    if (!is_file($fileOpt)) {
        fwrite(STDERR, "ERRO: arquivo não encontrado: $fileOpt\n");
        exit(2);
    }
    $lines = file($fileOpt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '') { $skus[] = $l; }
    }
}

/**
 * Iterate products and fix image roles
 */
$fixed = 0; $processed = 0; $errors = 0;

$processProduct = function (Magento\Catalog\Model\Product $product) use (&$fixed, &$processed, $productRepository, $mediaConfig, $dryRun) {
    $processed++;
    $sku = (string)$product->getSku();
    $base = (string)$product->getData('image');
    $small = (string)$product->getData('small_image');
    $thumb = (string)$product->getData('thumbnail');

    // Determine first gallery image file if any
    $gallery = $product->getMediaGalleryImages();
    $firstGalleryFile = '';
    if ($gallery && $gallery->getSize() > 0) {
        foreach ($gallery as $img) {
            $file = (string)$img->getFile(); // relative path like /a/b/file.jpg
            if ($file) { $firstGalleryFile = $file; break; }
        }
    }

    // If base is empty or 'no_selection', try to use first gallery image
    $changed = false;
    $baseIsEmpty = ($base === '' || $base === 'no_selection');
    if ($baseIsEmpty && $firstGalleryFile !== '') {
        $product->setData('image', $firstGalleryFile);
        $base = $firstGalleryFile;
        $changed = true;
    }

    // If small/thumbnail are empty or 'no_selection', default to base if available, else first gallery
    if (($small === '' || $small === 'no_selection')) {
        if ($base !== '' && $base !== 'no_selection') {
            $product->setData('small_image', $base);
        } elseif ($firstGalleryFile !== '') {
            $product->setData('small_image', $firstGalleryFile);
        }
        $changed = true;
    }
    if (($thumb === '' || $thumb === 'no_selection')) {
        if ($base !== '' && $base !== 'no_selection') {
            $product->setData('thumbnail', $base);
        } elseif ($firstGalleryFile !== '') {
            $product->setData('thumbnail', $firstGalleryFile);
        }
        $changed = true;
    }

    if ($changed) {
        if ($dryRun) {
            echo "[DRY] $sku - image: {$product->getData('image')} | small: {$product->getData('small_image')} | thumb: {$product->getData('thumbnail')}\n";
        } else {
            try {
                $productRepository->save($product);
                $fixed++;
                echo "[OK] $sku - image: {$product->getData('image')} | small: {$product->getData('small_image')} | thumb: {$product->getData('thumbnail')}\n";
            } catch (\Throwable $e) {
                $GLOBALS['errors'] = ($GLOBALS['errors'] ?? 0) + 1;
                fwrite(STDERR, "[ERRO] $sku - " . $e->getMessage() . "\n");
            }
        }
    } else {
        echo "[SKIP] $sku - já possui papéis de imagem válidos.\n";
    }
};

if ($skus) {
    foreach ($skus as $sku) {
        try {
            /** @var Magento\Catalog\Model\Product $product */
            $product = $productRepository->get($sku, false, null, true);
            $processProduct($product);
        } catch (Magento\Framework\Exception\NoSuchEntityException $e) {
            fwrite(STDERR, "[WARN] SKU não encontrado: $sku\n");
            continue;
        }
    }
} else {
    // All products (limit safety)
    $collection = $productCollectionFactory->create();
    $collection->addAttributeToSelect(['image','small_image','thumbnail','media_gallery']);
    $collection->setPageSize(1000); // safety limit
    foreach ($collection as $product) {
        $processProduct($product);
    }
}

echo "\nProcessados: $processed | Corrigidos: $fixed | Erros: " . ($GLOBALS['errors'] ?? 0) . "\n";
exit(0);

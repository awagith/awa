<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', ['sku:']);
if (!isset($opts['sku']) || $opts['sku'] === '') {
    fwrite(STDERR, "Uso: php scripts/check_product_media.php --sku <SKU>\n");
    exit(2);
}
$sku = (string)$opts['sku'];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
/** @var Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catColFactory */
$catColFactory = $om->get(Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);

try {
    $product = $productRepository->get($sku);
} catch (Magento\Framework\Exception\NoSuchEntityException $e) {
    fwrite(STDERR, "Produto não encontrado: $sku\n");
    exit(3);
}

$name = (string)$product->getName();
$urlKey = (string)$product->getUrlKey();
$image = (string)$product->getData('image');
$small = (string)$product->getData('small_image');
$thumb = (string)$product->getData('thumbnail');
$media = $product->getMediaGalleryImages();
$mediaCount = $media ? $media->getSize() : 0;
$catIds = $product->getCategoryIds() ?: [];

$catNames = [];
if ($catIds) {
    $col = $catColFactory->create();
    $col->addAttributeToSelect('name')->addAttributeToFilter('entity_id', ['in' => $catIds]);
    foreach ($col as $c) { $catNames[] = $c->getName(); }
}

echo "SKU: $sku\n";
echo "Nome: $name\n";
echo "URL Key: $urlKey\n";
echo "Imagem base: $image | small: $small | thumb: $thumb\n";
echo "Galeria: $mediaCount imagem(ns)\n";
echo "Categorias (" . count($catIds) . "): " . implode(' > ', $catNames) . "\n";
exit(0);

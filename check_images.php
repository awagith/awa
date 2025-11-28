<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();

$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$collection = $obj->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create();
$collection->addAttributeToSelect(['name', 'image', 'small_image', 'thumbnail']);
$collection->setPageSize(5);

echo "Checking first 5 products images:\n";
foreach ($collection as $product) {
    echo "ID: " . $product->getId() . " - Name: " . $product->getName() . "\n";
    echo "  Image: " . $product->getImage() . "\n";
    echo "  Small: " . $product->getSmallImage() . "\n";
    echo "  Thumb: " . $product->getThumbnail() . "\n";
    
    $imagePath = 'pub/media/catalog/product' . $product->getImage();
    if (file_exists($imagePath)) {
        echo "  [OK] File exists: $imagePath\n";
    } else {
        echo "  [FAIL] File missing: $imagePath\n";
    }
}

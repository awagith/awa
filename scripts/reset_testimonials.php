<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

$collection = $objectManager->create(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\Collection::class);

echo "Deletando " . $collection->getSize() . " testimonials...\n";
foreach ($collection as $testimonial) {
    $testimonial->delete();
}

echo "✅ Testimonials deletados\n";
echo "Aguarde execução do seed...\n\n";

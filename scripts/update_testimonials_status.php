<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

$collection = $objectManager->create(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\Collection::class);

$updated = 0;
foreach ($collection as $testimonial) {
    $testimonial->setData('is_active', 1);
    $testimonial->setData('status', 1);
    $testimonial->save();
    $updated++;
    echo "✅ Ativado: {$testimonial->getName()} (is_active=1)\n";
}

echo "\n🎯 Total atualizado: {$updated} testimonials\n\n";

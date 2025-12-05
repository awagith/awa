<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('frontend');

$collection = $objectManager->create(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\Collection::class);

echo "Total de testimonials: " . $collection->getSize() . PHP_EOL;
echo str_repeat("=", 80) . PHP_EOL;

foreach ($collection as $testimonial) {
    echo "ID: {$testimonial->getTestimonialsId()}" . PHP_EOL;
    echo "Nome: {$testimonial->getName()}" . PHP_EOL;
    echo "Email: {$testimonial->getEmail()}" . PHP_EOL;
    echo "Rating: {$testimonial->getRating()}" . PHP_EOL;
    echo "Status: {$testimonial->getStatus()}" . PHP_EOL;
    echo "Avatar: {$testimonial->getAvatar()}" . PHP_EOL;
    echo str_repeat("-", 80) . PHP_EOL;
}

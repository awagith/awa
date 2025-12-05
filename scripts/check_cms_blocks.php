<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('frontend');

$blockRepository = $objectManager->get(\Magento\Cms\Api\BlockRepositoryInterface::class);
$searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder
    ->addFilter('identifier', '%testimonial%', 'like')
    ->create();

$blocks = $blockRepository->getList($searchCriteria)->getItems();

echo "Blocos CMS com 'testimonial':\n";
echo str_repeat("=", 80) . "\n";

foreach ($blocks as $block) {
    echo "ID: {$block->getId()}\n";
    echo "Identifier: {$block->getIdentifier()}\n";
    echo "Title: {$block->getTitle()}\n";
    echo "Active: " . ($block->isActive() ? 'Sim' : 'Não') . "\n";
    echo "Content (primeiros 200 caracteres):\n" . substr($block->getContent(), 0, 200) . "...\n";
    echo str_repeat("-", 80) . "\n";
}

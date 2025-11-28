<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();

$appState = $obj->get('Magento\Framework\App\State');
$appState->setAreaCode('adminhtml');

$pageRepository = $obj->get('Magento\Cms\Api\PageRepositoryInterface');
$blockRepository = $obj->get('Magento\Cms\Api\BlockRepositoryInterface');
$searchCriteriaBuilder = $obj->get('Magento\Framework\Api\SearchCriteriaBuilder');

echo "=== Listando Páginas CMS ===\n";
$searchCriteria = $searchCriteriaBuilder->create();
$pages = $pageRepository->getList($searchCriteria)->getItems();
foreach ($pages as $page) {
    echo "ID: " . $page->getId() . " | Identifier: " . $page->getIdentifier() . " | Title: " . $page->getTitle() . "\n";
}

echo "\n=== Listando Blocos CMS ===\n";
$searchCriteria = $searchCriteriaBuilder->create();
$blocks = $blockRepository->getList($searchCriteria)->getItems();
foreach ($blocks as $block) {
    echo "ID: " . $block->getId() . " | Identifier: " . $block->getIdentifier() . " | Title: " . $block->getTitle() . "\n";
}

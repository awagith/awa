<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();

$appState = $obj->get('Magento\Framework\App\State');
$appState->setAreaCode('adminhtml');

$blockRepository = $obj->get('Magento\Cms\Api\BlockRepositoryInterface');
$searchCriteriaBuilder = $obj->get('Magento\Framework\Api\SearchCriteriaBuilder');

$identifiers = ['footer_info', 'footer_menu'];

foreach ($identifiers as $identifier) {
    $searchCriteria = $searchCriteriaBuilder->addFilter('identifier', $identifier, 'eq')->create();
    $blocks = $blockRepository->getList($searchCriteria)->getItems();
    foreach ($blocks as $block) {
        echo "=== Block: $identifier ===\n";
        echo $block->getContent() . "\n\n";
    }
}

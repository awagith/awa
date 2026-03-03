<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$repo = $obj->get('Magento\Cms\Api\BlockRepositoryInterface');
try {
    $block = $repo->getById('category1_home5');
    echo $block->getContent();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

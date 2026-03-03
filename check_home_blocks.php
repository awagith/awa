<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$repo = $obj->get('Magento\Cms\Api\BlockRepositoryInterface');
try {
    echo "=== cms_home ===\n";
    echo $repo->getById('cms_home')->getContent() . "\n\n";

    echo "=== category1_home5 ===\n";
    echo $repo->getById('category1_home5')->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

echo "👥 Verificando Vendedores do Marketplace\n";
echo "======================================\n";

try {
    $sellerCollection = $objectManager->create('Webkul\Marketplace\Model\ResourceModel\Seller\Collection');
    $sellerCollection->addFieldToSelect('*');

    echo "Total de vendedores encontrados: " . $sellerCollection->getSize() . "\n\n";

    foreach ($sellerCollection as $seller) {
        echo "ID: " . $seller->getId() . "\n";
        echo "Nome da Loja: " . $seller->getShopUrl() . "\n";
        echo "Status: " . ($seller->getIsSeller() ? 'Ativo' : 'Inativo') . "\n";
        echo "-----------------------------------\n";
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

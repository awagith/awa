<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "👤 Criando Vendedor de Demonstração\n";
echo "===================================\n";

try {
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $websiteId = $storeManager->getWebsite()->getWebsiteId();
    $storeId = $storeManager->getStore()->getId();

    // 1. Criar Cliente
    $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
    $customer = $customerFactory->create();
    $customer->setWebsiteId($websiteId);
    $email = 'vendedor_demo@exemplo.com';
    
    $customer->loadByEmail($email);
    
    if (!$customer->getId()) {
        $customer->setEmail($email);
        $customer->setFirstname('Vendedor');
        $customer->setLastname('Demo');
        $customer->setPassword('Vendedor123!');
        $customer->save();
        echo "✅ Cliente criado: " . $email . " (ID: " . $customer->getId() . ")\n";
    } else {
        echo "ℹ️  Cliente já existe: " . $email . " (ID: " . $customer->getId() . ")\n";
    }

    // 2. Registrar como Vendedor
    $sellerFactory = $objectManager->get('Webkul\Marketplace\Model\SellerFactory');
    $seller = $sellerFactory->create();
    $seller->load($customer->getId(), 'seller_id');

    if (!$seller->getId()) {
        $seller->setSellerId($customer->getId());
        $seller->setStoreId($storeId);
        $seller->setIsSeller(1);
        $seller->setShopUrl('loja-demo');
        $seller->setShopTitle('Loja de Demonstração');
        $seller->setCountryPic('BR');
        $seller->setCity('São Paulo');
        $seller->setCountry('BR');
        $seller->setCreatedAt(date('Y-m-d H:i:s'));
        $seller->setUpdatedAt(date('Y-m-d H:i:s'));
        $seller->setAdminNotification(1);
        $seller->save();
        echo "✅ Vendedor registrado com sucesso!\n";
        echo "🔗 URL da Loja: " . $storeManager->getStore()->getBaseUrl() . "marketplace/seller/profile/shop/loja-demo\n";
    } else {
        echo "ℹ️  Vendedor já registrado.\n";
    }

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

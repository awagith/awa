<?php
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Email\Model\TemplateFactory;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Throwable $e) {
}

/** @var TemplateFactory $templateFactory */
$templateFactory = $objectManager->get(TemplateFactory::class);
/** @var TemplateCollectionFactory $templateCollectionFactory */
$templateCollectionFactory = $objectManager->get(TemplateCollectionFactory::class);
/** @var ConfigResource $configResource */
$configResource = $objectManager->get(ConfigResource::class);

$map = [
    'grupoawamotos_newsletter_confirm_ptbr' => ['newsletter/subscription/confirm_email_template'],
    'grupoawamotos_newsletter_success_ptbr' => ['newsletter/subscription/success_email_template'],
    'grupoawamotos_newsletter_unsubscribe_ptbr' => ['newsletter/subscription/un_email_template'],
];

foreach ($map as $code => $paths) {
    $collection = $templateCollectionFactory->create();
    $collection->addFieldToFilter('template_code', $code);
    $template = $collection->getFirstItem();
    $templateId = (int) $template->getId();
    if (!$templateId) {
        echo sprintf("[ERRO] Template %s não encontrado. Execute scripts/configurar_emails_ptbr.php primeiro.\n", $code);
        continue;
    }

    foreach ($paths as $path) {
        $configResource->saveConfig($path, $templateId, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    echo sprintf("[OK] %s vinculado (%d)\n", $code, $templateId);
}

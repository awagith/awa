<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

/**
 * Garante que uma opção exista para um atributo de produto (ex: manufacturer)
 * Uso:
 *  php scripts/ensure_attribute_option.php manufacturer "AWA Motos"
 */

$attrCode = $argv[1] ?? null;
$label    = $argv[2] ?? null;
if (!$attrCode || !$label) {
    fwrite(STDERR, "Uso: php scripts/ensure_attribute_option.php <attribute_code> <option_label>\n");
    exit(2);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Eav\Model\Config $eavConfig */
$eavConfig = $om->get(Magento\Eav\Model\Config::class);
/** @var Magento\Eav\Api\AttributeOptionManagementInterface $optionManagement */
$optionManagement = $om->get(Magento\Eav\Api\AttributeOptionManagementInterface::class);
/** @var Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory */
$optionFactory = $om->get(Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class);
/** @var Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $labelFactory */
$labelFactory = $om->get(Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory::class);

$entityType = 'catalog_product';

try {
    $attribute = $eavConfig->getAttribute($entityType, $attrCode);
    if (!$attribute || !$attribute->getId()) {
        throw new \RuntimeException("Atributo '$attrCode' não encontrado em $entityType");
    }
    $source = $attribute->getSource();
    $options = $source ? $source->getAllOptions(false) : [];
    foreach ($options as $opt) {
        if (isset($opt['label']) && trim((string)$opt['label']) === $label) {
            echo "✓ Opção já existe para '$attrCode': {$label} (ID {$opt['value']})\n";
            exit(0);
        }
    }

    $option = $optionFactory->create();
    $option->setLabel($label);
    $option->setSortOrder(0);
    $option->setIsDefault(false);

    $storeLabel = $labelFactory->create();
    $storeLabel->setStoreId(0);
    $storeLabel->setLabel($label);
    $option->setStoreLabels([$storeLabel]);

    $result = $optionManagement->add($entityType, $attrCode, $option);
    if ($result) {
        echo "✅ Opção criada para '$attrCode': {$label}\n";
        exit(0);
    }
    echo "⚠️  Não foi possível criar a opção (retorno falso).\n";
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Erro: " . $e->getMessage() . "\n");
    exit(1);
}

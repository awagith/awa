<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

/**
 * Script rápido para popular atributos de fitment em produtos existentes.
 * Uso:
 * php scripts/populate_fitment_demo.php LIMIT=20 MARCAS="Honda:Yamaha" ANOS="2020:2021:2022" MODELOS="CG 160:CB 500:Fazer 250"
 */

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();

/** @var \Magento\Framework\App\State $state */
$state = $obj->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('adminhtml'); } catch (Exception $e) {}

$limit = (int)($argv[1] ?? getenv('LIMIT') ?: 20);
$marcasInput = getenv('MARCAS') ?: 'Honda:Yamaha:Kawasaki:Suzuki';
$modelosInput = getenv('MODELOS') ?: 'CG 160:CB 500:Fazer 250:Ninja 400:GSX S750';
$anosInput = getenv('ANOS') ?: '2019:2020:2021:2022:2023';

$marcas = array_filter(array_map('trim', explode(':', $marcasInput)));
$modelos = array_filter(array_map('trim', explode(':', $modelosInput)));
$anos = array_filter(array_map('trim', explode(':', $anosInput)));

if (!$marcas || !$modelos || !$anos) {
    echo "Listas vazias. Forneça MARCAS, MODELOS e ANOS.\n";
    exit(1);
}

/** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory */
$collectionFactory = $obj->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
$collection = $collectionFactory->create();
$collection->addAttributeToSelect(['marca_moto','modelo_moto','ano_moto','name']);
$collection->setPageSize($limit)->setCurPage(1);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepo */
$productRepo = $obj->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$updated = 0; $skipped = 0; $errors = 0;

foreach ($collection as $product) {
    try {
        $marca = $marcas[array_rand($marcas)];
        $modelo = $modelos[array_rand($modelos)];
        $ano = $anos[array_rand($anos)];
        $needSave = false;
        if ($product->getData('marca_moto') !== $marca) { $product->setData('marca_moto', $marca); $needSave = true; }
        if ($product->getData('modelo_moto') !== $modelo) { $product->setData('modelo_moto', $modelo); $needSave = true; }
        if ($product->getData('ano_moto') !== $ano) { $product->setData('ano_moto', $ano); $needSave = true; }
        if ($needSave) {
            $productRepo->save($product);
            $updated++;
            echo "Atualizado: {$product->getSku()} => $marca / $modelo / $ano\n";
        } else { $skipped++; }
    } catch (Throwable $t) {
        $errors++;
        echo "Erro em SKU {$product->getSku()}: {$t->getMessage()}\n";
    }
}

echo "\nResumo: atualizados=$updated skipped=$skipped erros=$errors\n";
echo "Execute depois: curl 'https://SEU_HOST/fitment/ajax/models?marca=Honda' para validar.\n";
exit(0);
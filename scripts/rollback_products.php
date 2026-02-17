<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

/**
 * Remove produtos por lista de SKUs (um SKU por linha)
 * Uso:
 *   php scripts/rollback_products.php /caminho/para/skus.txt
 */

require __DIR__ . '/../app/bootstrap.php';

$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Uso: php scripts/rollback_products.php <arquivo_skus>\n");
    exit(2);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);

$skus = array_values(array_filter(array_map('trim', file($file) ?: [])));
$deleted = 0; $notFound = 0; $errors = 0;

foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku);
        $productRepository->delete($product);
        echo "✓ Deletado: $sku\n";
        $deleted++;
    } catch (Magento\Framework\Exception\NoSuchEntityException $e) {
        echo "- Não encontrado: $sku\n";
        $notFound++;
    } catch (\Throwable $e) {
        echo "✗ Erro ao deletar $sku: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\nResumo: deletados=$deleted, nao_encontrados=$notFound, erros=$errors\n";
exit($errors > 0 ? 1 : 0);

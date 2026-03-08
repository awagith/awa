<?php
/**
 * Quick Order Validate Controller
 * Endpoint: POST b2b/quickorder/validate
 * Validates a batch of SKUs (without adding to cart) and returns product details.
 * Used by the header Quick Order modal for the "Validar SKUs" step.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Quickorder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class Validate implements HttpPostActionInterface
{
    private const MAX_ITEMS = 120;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly CustomerSession $customerSession,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Formulário inválido. Tente novamente.'),
                'items'   => [],
            ]);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Faça login para validar produtos.'),
                'items'   => [],
            ]);
        }

        $rawItems = $this->request->getParam('items', []);
        $items = $this->parseItems(is_array($rawItems) ? $rawItems : []);

        if ($items === []) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Informe pelo menos um SKU.'),
                'items'   => [],
            ]);
        }

        $validated = [];

        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = $item['qty'];

            try {
                $product = $this->productRepository->get($sku);

                try {
                    $stockItem = $this->stockRegistry->getStockItem((int) $product->getId());
                    $stockQty  = (int) $stockItem->getQty();
                    $inStock   = $stockItem->getIsInStock() && $stockQty > 0;
                } catch (\Exception $e) {
                    $stockQty = 0;
                    $inStock  = false;
                }

                $status = 'found';
                if (!$product->isSaleable()) {
                    $status = 'unavailable';
                } elseif (!$inStock) {
                    $status = 'out_of_stock';
                }

                $validated[] = [
                    'sku'       => $sku,
                    'qty'       => $qty,
                    'status'    => $status,
                    'name'      => (string) $product->getName(),
                    'price'     => $this->priceCurrency->format(
                        (float) $product->getFinalPrice(),
                        false
                    ),
                    'stock_qty' => $stockQty,
                    'in_stock'  => $inStock,
                ];
            } catch (NoSuchEntityException) {
                $validated[] = [
                    'sku'    => $sku,
                    'qty'    => $qty,
                    'status' => 'not_found',
                    'name'   => '',
                    'price'  => '',
                ];
            } catch (\Exception $e) {
                $this->logger->error('[B2B QuickOrder Validate] Error', [
                    'sku'       => $sku,
                    'exception' => $e,
                ]);
                $validated[] = [
                    'sku'    => $sku,
                    'qty'    => $qty,
                    'status' => 'error',
                    'name'   => '',
                    'price'  => '',
                ];
            }
        }

        $foundCount = count(array_filter($validated, fn($i) => $i['status'] === 'found'));

        return $result->setData([
            'success' => true,
            'items'   => $validated,
            'summary' => [
                'total'     => count($validated),
                'found'     => $foundCount,
                'not_found' => count(array_filter($validated, fn($i) => $i['status'] === 'not_found')),
                'issues'    => count($validated) - $foundCount,
            ],
        ]);
    }

    /**
     * @param array<int, mixed> $rawItems
     * @return array<int, array{sku:string, qty:int}>
     */
    private function parseItems(array $rawItems): array
    {
        $result = [];
        $seen   = [];

        foreach ($rawItems as $item) {
            $sku = trim((string) ($item['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $key = strtolower($sku);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[]   = ['sku' => $sku, 'qty' => $qty];
            }

            if (count($result) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $result;
    }
}

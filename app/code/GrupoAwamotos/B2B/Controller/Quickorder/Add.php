<?php
/**
 * Quick Order Add to Cart Controller
 * Processes multiple SKUs and adds to cart
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Quickorder;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Add implements HttpPostActionInterface
{
    private $request;
    private $resultJsonFactory;
    private $formKeyValidator;
    private $productRepository;
    private $cart;
    private $logger;
    private $customerSession;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        ProductRepositoryInterface $productRepository,
        Cart $cart,
        LoggerInterface $logger,
        CustomerSession $customerSession
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData([
                'success' => false,
                'message' => __('Formulário inválido. Tente novamente.'),
                'added' => [],
                'errors' => []
            ]);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => __('Faça login para usar o Pedido Rápido.'),
                'added' => [],
                'errors' => []
            ]);
        }

        $items = $this->request->getParam('items', []);

        $added = [];
        $errors = [];

        foreach ($items as $item) {
            $sku = trim($item['sku'] ?? '');
            $qty = (int) ($item['qty'] ?? 1);

            if (empty($sku) || $qty < 1) {
                continue;
            }

            try {
                $product = $this->productRepository->get($sku);

                if (!$product->isSaleable()) {
                    $errors[] = [
                        'sku' => $sku,
                        'message' => __('Produto indisponível')
                    ];
                    continue;
                }

                $this->cart->addProduct($product, ['qty' => $qty]);
                $added[] = [
                    'sku' => $sku,
                    'name' => $product->getName(),
                    'qty' => $qty
                ];

            } catch (NoSuchEntityException $e) {
                $errors[] = [
                    'sku' => $sku,
                    'message' => __('SKU não encontrado')
                ];
            } catch (\Exception $e) {
                $this->logger->error('Quick Order Error: ' . $e->getMessage());
                $errors[] = [
                    'sku' => $sku,
                    'message' => __('Erro ao adicionar produto')
                ];
            }
        }

        if (!empty($added)) {
            $this->cart->save();
        }

        return $result->setData([
            'success' => !empty($added),
            'added' => $added,
            'errors' => $errors,
            'cartUrl' => $this->cart->getQuote()->getStore()->getUrl('checkout/cart'),
            'message' => count($added) > 0
                ? __('%1 produto(s) adicionado(s) ao carrinho', count($added))
                : __('Nenhum produto foi adicionado')
        ]);
    }
}

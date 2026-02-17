<?php
/**
 * Observer para criar automaticamente cotações para oportunidades de Cross-sell
 */
namespace GrupoAwamotos\RexisML\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class AutoCreateQuoteObserver implements ObserverInterface
{
    protected $recomendacaoCollectionFactory;
    protected $customerRepository;
    protected $productRepository;
    protected $cartManagement;
    protected $cartRepository;
    protected $storeManager;
    protected $logger;

    public function __construct(
        CollectionFactory $recomendacaoCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Executar quando um pedido é concluído
     * Criar cotação automática para próximas compras recomendadas
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            $customerId = $order->getCustomerId();

            if (!$customerId) {
                return; // Pedido de guest
            }

            // Buscar recomendações de Cross-sell para este cliente
            $collection = $this->recomendacaoCollectionFactory->create();
            $collection->addFieldToFilter('identificador_cliente', $customerId)
                      ->addFieldToFilter('classificacao_produto', 'Oportunidade Cross-sell')
                      ->addFieldToFilter('pred', ['gteq' => 0.80]) // Score muito alto
                      ->addFieldToFilter('previsao_gasto_round_up', ['gteq' => 200]) // Valor mínimo
                      ->setOrder('pred', 'DESC')
                      ->setPageSize(3); // Máximo 3 produtos

            if ($collection->getSize() === 0) {
                return;
            }

            // Criar carrinho de cotação
            $customer = $this->customerRepository->getById($customerId);
            $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
            $quote = $this->cartRepository->get($cartId);

            $quote->setIsActive(false); // Marcar como cotação (não carrinho ativo)
            $quote->setCustomer($customer);
            $quote->setStore($this->storeManager->getStore());

            // Adicionar produtos recomendados
            $addedProducts = 0;
            foreach ($collection as $recommendation) {
                try {
                    $product = $this->productRepository->get($recommendation->getIdentificadorProduto());
                    $quote->addProduct($product, 1);
                    $addedProducts++;
                } catch (\Exception $e) {
                    $this->logger->warning("REXIS ML AutoQuote: Produto não encontrado - " . $recommendation->getIdentificadorProduto());
                    continue;
                }
            }

            if ($addedProducts > 0) {
                // Adicionar comentário na cotação
                $quote->setCustomerNote(
                    "Cotação automática gerada pelo REXIS ML baseada em análise preditiva. " .
                    "Produtos recomendados com alta probabilidade de compra."
                );

                $quote->collectTotals();
                $this->cartRepository->save($quote);

                $this->logger->info(sprintf(
                    'REXIS ML: Cotação automática #%s criada para cliente #%d com %d produtos',
                    $quote->getId(),
                    $customerId,
                    $addedProducts
                ));
            }

        } catch (\Exception $e) {
            $this->logger->error('REXIS ML AutoCreateQuote: ' . $e->getMessage());
        }
    }
}

<?php
/**
 * AJAX Controller para buscar recomendações em tempo real
 */
namespace GrupoAwamotos\RexisML\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Catalog\Helper\Image as ImageHelper;

class GetRecommendations extends Action
{
    protected $resultJsonFactory;
    protected $customerSession;
    protected $recomendacaoCollectionFactory;
    protected $productRepository;
    protected $pricingHelper;
    protected $imageHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        CollectionFactory $recomendacaoCollectionFactory,
        ProductRepositoryInterface $productRepository,
        PricingHelper $pricingHelper,
        ImageHelper $imageHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
        $this->productRepository = $productRepository;
        $this->pricingHelper = $pricingHelper;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Execute AJAX request
     *
     * GET /rexisml/ajax/getrecommendations
     * Params:
     *   - classificacao (optional)
     *   - limit (optional, default: 4)
     *   - minScore (optional, default: 0.7)
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        // Verificar se cliente está logado
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => 'Cliente não está logado',
                'recommendations' => []
            ]);
        }

        try {
            $customerId = $this->customerSession->getCustomerId();
            $classificacao = $this->getRequest()->getParam('classificacao');
            $limit = (int)$this->getRequest()->getParam('limit', 4);
            $minScore = (float)$this->getRequest()->getParam('minScore', 0.7);

            // Buscar recomendações
            $collection = $this->recomendacaoCollectionFactory->create();
            $collection->addFieldToFilter('identificador_cliente', $customerId)
                      ->addFieldToFilter('pred', ['gteq' => $minScore])
                      ->setOrder('pred', 'DESC')
                      ->setPageSize($limit);

            if ($classificacao) {
                $collection->addFieldToFilter('classificacao_produto', $classificacao);
            }

            $recommendations = [];
            foreach ($collection as $item) {
                try {
                    $product = $this->productRepository->get($item->getIdentificadorProduto());

                    if (!$product->isSaleable()) {
                        continue;
                    }

                    $recommendations[] = [
                        'product_id' => $product->getId(),
                        'sku' => $product->getSku(),
                        'name' => $product->getName(),
                        'url' => $product->getProductUrl(),
                        'image' => $this->imageHelper->init($product, 'product_page_image_small')->getUrl(),
                        'price' => $this->pricingHelper->currency($product->getFinalPrice(), true, false),
                        'price_value' => $product->getFinalPrice(),
                        'score' => round($item->getPred() * 100, 1),
                        'classificacao' => $item->getClassificacaoProduto(),
                        'predicted_value' => $item->getPrevisaoGastoRoundUp(),
                        'recencia' => $item->getRecencia()
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $result->setData([
                'success' => true,
                'recommendations' => $recommendations,
                'total' => count($recommendations)
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'recommendations' => []
            ]);
        }
    }
}

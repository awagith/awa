<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use GrupoAwamotos\ERPIntegration\Model\CircuitBreaker;

class ResetCircuit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'GrupoAwamotos_ERPIntegration::sync';

    private JsonFactory $jsonFactory;
    private CircuitBreaker $circuitBreaker;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CircuitBreaker $circuitBreaker
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->circuitBreaker = $circuitBreaker;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $this->circuitBreaker->reset();

            return $result->setData([
                'success' => true,
                'message' => 'Circuit Breaker resetado com sucesso',
                'stats' => $this->circuitBreaker->getStats(),
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

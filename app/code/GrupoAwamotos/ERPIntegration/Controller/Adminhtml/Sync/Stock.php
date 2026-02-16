<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use GrupoAwamotos\ERPIntegration\Api\StockSyncInterface;

class Stock extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'GrupoAwamotos_ERPIntegration::sync';

    private JsonFactory $jsonFactory;
    private StockSyncInterface $stockSync;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        StockSyncInterface $stockSync
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->stockSync = $stockSync;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $syncResult = $this->stockSync->syncAll();
            return $result->setData(array_merge(['success' => true], $syncResult));
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

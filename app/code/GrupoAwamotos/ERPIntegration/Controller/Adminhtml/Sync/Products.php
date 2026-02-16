<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use GrupoAwamotos\ERPIntegration\Api\ProductSyncInterface;

class Products extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'GrupoAwamotos_ERPIntegration::sync';

    private JsonFactory $jsonFactory;
    private ProductSyncInterface $productSync;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductSyncInterface $productSync
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productSync = $productSync;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $syncResult = $this->productSync->syncAll();
            return $result->setData(array_merge(['success' => true], $syncResult));
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

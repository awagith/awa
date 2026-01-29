<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use GrupoAwamotos\ERPIntegration\Api\CustomerSyncInterface;

class Customers extends Action
{
    public const ADMIN_RESOURCE = 'GrupoAwamotos_ERPIntegration::sync';

    private JsonFactory $jsonFactory;
    private CustomerSyncInterface $customerSync;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CustomerSyncInterface $customerSync
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->customerSync = $customerSync;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $syncResult = $this->customerSync->syncAll();
            return $result->setData(array_merge(['success' => true], $syncResult));
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

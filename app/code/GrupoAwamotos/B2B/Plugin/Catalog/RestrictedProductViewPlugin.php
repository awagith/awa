<?php
/**
 * B2B Restricted Product View Plugin - Block access to restricted products
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Catalog;

use Magento\Catalog\Controller\Product\View;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use GrupoAwamotos\B2B\Helper\Data as B2BHelper;

class RestrictedProductViewPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var B2BHelper
     */
    private $b2bHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        B2BHelper $b2bHelper,
        RequestInterface $request
    ) {
        $this->productRepository = $productRepository;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->b2bHelper = $b2bHelper;
        $this->request = $request;
    }

    /**
     * Check B2B restrictions before viewing product
     *
     * @param View $subject
     * @return null|\Magento\Framework\Controller\Result\Redirect
     */
    public function beforeExecute(View $subject)
    {
        if (!$this->b2bHelper->isEnabled()) {
            return null;
        }

        $productId = (int) $this->request->getParam('id');
        if (!$productId) {
            return null;
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return null;
        }

        $isB2BExclusive = (bool) $product->getData('b2b_exclusive');
        $allowedGroups = $product->getData('b2b_customer_groups');

        if (!$isB2BExclusive && empty($allowedGroups)) {
            return null; // No restrictions
        }

        $isLoggedIn = $this->customerSession->isLoggedIn();
        $customerGroupId = $isLoggedIn ? (int) $this->customerSession->getCustomerGroupId() : 0;
        $isB2BCustomer = in_array($customerGroupId, $this->b2bHelper->getB2BGroupIds());

        // Check B2B exclusive
        if ($isB2BExclusive && !$isB2BCustomer) {
            $this->messageManager->addNoticeMessage(
                __('Este produto é exclusivo para clientes B2B. Faça login ou cadastre sua empresa.')
            );
            
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('b2b/register');
        }

        // Check group restrictions
        if (!empty($allowedGroups) && $isB2BCustomer) {
            if (!$this->isGroupAllowed($allowedGroups, $customerGroupId)) {
                $this->messageManager->addNoticeMessage(
                    __('Este produto não está disponível para o seu grupo de clientes.')
                );
                
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('/');
            }
        }

        return null;
    }

    /**
     * Check if customer group is allowed
     *
     * @param string|array $allowedGroups
     * @param int $customerGroupId
     * @return bool
     */
    private function isGroupAllowed($allowedGroups, int $customerGroupId): bool
    {
        if (is_string($allowedGroups)) {
            $allowedGroups = explode(',', $allowedGroups);
        }

        if (!is_array($allowedGroups)) {
            return true;
        }

        // Map group IDs to option values
        $groupMap = [
            4 => 'B2B Atacado',
            5 => 'B2B VIP', 
            6 => 'B2B Revendedor'
        ];

        $customerGroupName = $groupMap[$customerGroupId] ?? '';

        foreach ($allowedGroups as $group) {
            $group = trim($group);
            if ($group === 'Todos os Grupos B2B' || $group === $customerGroupName) {
                return true;
            }
        }

        return false;
    }
}

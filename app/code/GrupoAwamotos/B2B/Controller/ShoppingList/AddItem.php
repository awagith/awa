<?php
/**
 * Add Item to Shopping List Controller
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\ShoppingList;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use GrupoAwamotos\B2B\Model\ShoppingListService;

class AddItem implements HttpPostActionInterface
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ShoppingListService
     */
    private $shoppingListService;

    /**
     * @param CustomerSession $customerSession
     * @param RedirectFactory $redirectFactory
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param ShoppingListService $shoppingListService
     */
    public function __construct(
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory,
        RequestInterface $request,
        ManagerInterface $messageManager,
        ShoppingListService $shoppingListService
    ) {
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->shoppingListService = $shoppingListService;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            if ($this->request->isAjax()) {
                $result = $this->jsonFactory->create();
                return $result->setData(['success' => false, 'message' => __('Por favor, faça login.')]);
            }
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        $listId = (int)$this->request->getParam('list_id');
        $productId = (int)$this->request->getParam('product_id');
        $qty = (float)$this->request->getParam('qty', 1);

        try {
            $item = $this->shoppingListService->addItem($listId, $productId, $qty);
            $message = __('Produto adicionado à lista.');

            if ($this->request->isAjax()) {
                $result = $this->jsonFactory->create();
                return $result->setData([
                    'success' => true,
                    'message' => $message,
                    'item_id' => $item->getId()
                ]);
            }

            $this->messageManager->addSuccessMessage($message);
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('b2b/shoppinglist/view', ['id' => $listId]);

        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                $result = $this->jsonFactory->create();
                return $result->setData(['success' => false, 'message' => $e->getMessage()]);
            }

            $this->messageManager->addErrorMessage($e->getMessage());
            $redirect = $this->redirectFactory->create();
            return $redirect->setRefererUrl();
        }
    }
}

<?php
/**
 * Quick Add to Shopping List Controller
 *
 * Adds a product to the customer's default (first) shopping list.
 * Creates the default list automatically if none exists.
 * Returns JSON for AJAX calls, redirects otherwise.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\ShoppingList;

use GrupoAwamotos\B2B\Model\ShoppingListService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

class QuickAdd implements HttpPostActionInterface
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly JsonFactory $jsonFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly Http $request,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly ManagerInterface $messageManager,
        private readonly ShoppingListService $shoppingListService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $isAjax = $this->request->isAjax();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->respond($isAjax, false, (string) __('Sessão expirada. Recarregue a página.'));
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $this->respond($isAjax, false, (string) __('Faça login para salvar produtos na lista.'), 'login_required');
        }

        $productId = (int) $this->request->getParam('product_id');
        $qty = max(1.0, (float) $this->request->getParam('qty', 1));

        if (!$productId) {
            return $this->respond($isAjax, false, (string) __('Produto inválido.'));
        }

        try {
            $list = $this->getOrCreateDefaultList();
            $this->shoppingListService->addItem($list->getId(), $productId, $qty);

            $message = (string) __('Produto salvo na lista "%1".', $list->getData('name'));
            return $this->respond($isAjax, true, $message, null, (int) $list->getId());

        } catch (LocalizedException $e) {
            return $this->respond($isAjax, false, $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('[B2B QuickAdd] product_id=' . $productId . ' — ' . $e->getMessage());
            return $this->respond($isAjax, false, (string) __('Erro ao salvar produto. Tente novamente.'));
        }
    }

    /**
     * Get the customer's most-recently-updated list, or create "Minha Lista" if none exists.
     *
     * @return \GrupoAwamotos\B2B\Model\ShoppingList
     * @throws LocalizedException
     */
    private function getOrCreateDefaultList()
    {
        $lists = $this->shoppingListService->getCustomerLists();
        $defaultList = $lists->getFirstItem();

        if ($defaultList->getId()) {
            return $defaultList;
        }

        return $this->shoppingListService->createList((string) __('Minha Lista'));
    }

    /**
     * @param bool $isAjax
     * @param bool $success
     * @param string $message
     * @param string|null $errorCode
     * @param int|null $listId
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function respond(
        bool $isAjax,
        bool $success,
        string $message,
        ?string $errorCode = null,
        ?int $listId = null
    ) {
        if ($isAjax) {
            $result = $this->jsonFactory->create();
            $data = ['success' => $success, 'message' => $message];
            if ($errorCode) {
                $data['error_code'] = $errorCode;
            }
            if ($listId) {
                $data['list_id'] = $listId;
            }
            return $result->setData($data);
        }

        if ($success) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }

        return $this->redirectFactory->create()->setRefererUrl();
    }
}

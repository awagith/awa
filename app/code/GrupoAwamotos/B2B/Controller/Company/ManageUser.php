<?php
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Company;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use GrupoAwamotos\B2B\Model\CompanyService;
use GrupoAwamotos\B2B\Model\Company;

class ManageUser implements HttpPostActionInterface
{
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private FormKeyValidator $formKeyValidator;
    private Session $customerSession;
    private CompanyService $companyService;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        FormKeyValidator $formKeyValidator,
        Session $customerSession,
        CompanyService $companyService
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->companyService = $companyService;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Formulário inválido. Tente novamente.')]);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Login necessário.')]);
        }

        $customerId = (int) $this->customerSession->getCustomerId();
        $company = $this->companyService->getCompanyForCustomer($customerId);

        if (!$company) {
            return $result->setData(['success' => false, 'message' => __('Empresa não encontrada.')]);
        }

        $role = $this->companyService->getUserRole($customerId);
        if ($role !== Company::ROLE_ADMIN) {
            return $result->setData(['success' => false, 'message' => __('Apenas administradores podem gerenciar usuários.')]);
        }

        $action = $this->request->getParam('action');
        $targetCustomerId = (int) $this->request->getParam('customer_id');
        $companyId = (int) $company->getId();

        try {
            return $this->processAction($result, $action, $companyId, $targetCustomerId);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function processAction($result, string $action, int $companyId, int $targetCustomerId)
    {
        switch ($action) {
            case 'add':
                $role = $this->request->getParam('role', Company::ROLE_BUYER);
                $this->companyService->addUser($companyId, $targetCustomerId, $role);
                return $result->setData(['success' => true, 'message' => __('Usuário adicionado.')]);

            case 'remove':
                $this->companyService->removeUser($companyId, $targetCustomerId);
                return $result->setData(['success' => true, 'message' => __('Usuário removido.')]);

            case 'update_role':
                $newRole = $this->request->getParam('role', Company::ROLE_BUYER);
                $this->companyService->updateUserRole($companyId, $targetCustomerId, $newRole);
                return $result->setData(['success' => true, 'message' => __('Papel atualizado.')]);

            default:
                return $result->setData(['success' => false, 'message' => __('Ação inválida.')]);
        }
    }
}

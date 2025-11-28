<?php
/**
 * Controller para processar cadastro B2B
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Register;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Mail\Template\TransportBuilder;
use GrupoAwamotos\B2B\Helper\CnpjValidator;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var CustomerFactory
     */
    private $customerModelFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Random
     */
    private $random;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CnpjValidator
     */
    private $cnpjValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        CustomerFactory $customerModelFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        Random $random,
        TransportBuilder $transportBuilder,
        CnpjValidator $cnpjValidator,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerModelFactory = $customerModelFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->random = $random;
        $this->transportBuilder = $transportBuilder;
        $this->cnpjValidator = $cnpjValidator;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            // Validar dados
            $data = $this->validateData();
            if (!$data) {
                return $resultRedirect->setPath('*/*/');
            }

            // Validar CNPJ
            if (!$this->cnpjValidator->validateLocal($data['cnpj'])) {
                $this->messageManager->addErrorMessage(__('CNPJ inválido. Por favor, verifique e tente novamente.'));
                return $resultRedirect->setPath('*/*/');
            }

            // Verificar se email já existe
            try {
                $existingCustomer = $this->customerRepository->get($data['email']);
                if ($existingCustomer->getId()) {
                    $this->messageManager->addErrorMessage(__('Já existe uma conta com este e-mail. Por favor, faça login ou use outro e-mail.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Email não existe, pode continuar
            }

            // Criar cliente
            $customer = $this->customerFactory->create();
            $customer->setEmail($data['email']);
            $customer->setFirstname($data['firstname']);
            $customer->setLastname($data['lastname']);
            $customer->setGroupId(7); // B2B Pendente
            $customer->setStoreId($this->storeManager->getStore()->getId());
            $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());

            // Atributos B2B
            $customer->setCustomAttribute('b2b_cnpj', $this->cnpjValidator->format($data['cnpj']));
            $customer->setCustomAttribute('b2b_razao_social', $data['razao_social']);
            $customer->setCustomAttribute('b2b_inscricao_estadual', $data['inscricao_estadual'] ?? '');
            $customer->setCustomAttribute('b2b_approved', 0);
            $customer->setCustomAttribute('b2b_company_phone', $data['phone'] ?? '');

            // Salvar cliente
            $savedCustomer = $this->customerRepository->save($customer);

            // Definir senha
            $customerModel = $this->customerModelFactory->create()->load($savedCustomer->getId());
            $customerModel->setPassword($data['password']);
            $customerModel->save();

            // Enviar email de confirmação
            $this->sendConfirmationEmail($savedCustomer, $data);

            // Notificar admin
            $this->notifyAdmin($savedCustomer, $data);

            $this->messageManager->addSuccessMessage(
                __('Cadastro realizado com sucesso! Seu acesso B2B será analisado e você receberá um e-mail em breve.')
            );

            // Login automático
            $this->customerSession->setCustomerDataAsLoggedIn($savedCustomer);

            return $resultRedirect->setPath('b2b/account/dashboard');

        } catch (\Exception $e) {
            $this->logger->error('B2B Registration Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(
                __('Ocorreu um erro ao processar seu cadastro. Por favor, tente novamente.')
            );
            return $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * Validate form data
     *
     * @return array|false
     */
    private function validateData()
    {
        $firstname = trim($this->request->getParam('firstname', ''));
        $lastname = trim($this->request->getParam('lastname', ''));
        $email = trim($this->request->getParam('email', ''));
        $password = $this->request->getParam('password', '');
        $passwordConfirm = $this->request->getParam('password_confirmation', '');
        $cnpj = preg_replace('/\D/', '', $this->request->getParam('cnpj', ''));
        $razaoSocial = trim($this->request->getParam('razao_social', ''));
        $inscricaoEstadual = trim($this->request->getParam('inscricao_estadual', ''));
        $phone = trim($this->request->getParam('phone', ''));

        $errors = [];

        if (empty($firstname)) {
            $errors[] = __('Nome é obrigatório.');
        }

        if (empty($lastname)) {
            $errors[] = __('Sobrenome é obrigatório.');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('E-mail inválido.');
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = __('A senha deve ter pelo menos 8 caracteres.');
        }

        if ($password !== $passwordConfirm) {
            $errors[] = __('As senhas não conferem.');
        }

        if (empty($cnpj) || strlen($cnpj) !== 14) {
            $errors[] = __('CNPJ é obrigatório e deve ter 14 dígitos.');
        }

        if (empty($razaoSocial)) {
            $errors[] = __('Razão Social é obrigatória.');
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->messageManager->addErrorMessage($error);
            }
            return false;
        }

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $password,
            'cnpj' => $cnpj,
            'razao_social' => $razaoSocial,
            'inscricao_estadual' => $inscricaoEstadual,
            'phone' => $phone
        ];
    }

    /**
     * Send confirmation email to customer
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array $data
     * @return void
     */
    private function sendConfirmationEmail($customer, array $data): void
    {
        try {
            $store = $this->storeManager->getStore();
            
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('grupoawamotos_b2b_registration_confirmation')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId()
                ])
                ->setTemplateVars([
                    'customer' => $customer,
                    'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'razao_social' => $data['razao_social'],
                    'cnpj' => $this->cnpjValidator->format($data['cnpj']),
                    'store' => $store
                ])
                ->setFromByScope('general')
                ->addTo($customer->getEmail(), $customer->getFirstname())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('B2B Registration Email Error: ' . $e->getMessage());
        }
    }

    /**
     * Notify admin about new B2B registration
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array $data
     * @return void
     */
    private function notifyAdmin($customer, array $data): void
    {
        try {
            $store = $this->storeManager->getStore();
            
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('grupoawamotos_b2b_registration_admin')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                    'store' => $store->getId()
                ])
                ->setTemplateVars([
                    'customer' => $customer,
                    'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'customer_email' => $customer->getEmail(),
                    'razao_social' => $data['razao_social'],
                    'cnpj' => $this->cnpjValidator->format($data['cnpj']),
                    'inscricao_estadual' => $data['inscricao_estadual'] ?? 'Não informado',
                    'phone' => $data['phone'] ?? 'Não informado',
                    'store' => $store,
                    'admin_url' => $store->getBaseUrl() . 'admin/customer/index/'
                ])
                ->setFromByScope('general')
                ->addTo('contato@grupoawamotos.com.br', 'Administrador')
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('B2B Admin Notification Error: ' . $e->getMessage());
        }
    }
}

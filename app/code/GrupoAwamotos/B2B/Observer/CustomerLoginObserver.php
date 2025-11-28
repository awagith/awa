<?php
/**
 * Observer for customer login - check approval status
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Observer;

use GrupoAwamotos\B2B\Helper\Config;
use GrupoAwamotos\B2B\Model\Customer\Attribute\Source\ApprovalStatus;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class CustomerLoginObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        Config $config,
        ManagerInterface $messageManager
    ) {
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        
        if (!$customer) {
            return;
        }
        
        $approvalStatus = $customer->getData('b2b_approval_status');
        
        // Se não tem status, considerar aprovado (compatibilidade)
        if (empty($approvalStatus)) {
            return;
        }
        
        switch ($approvalStatus) {
            case ApprovalStatus::STATUS_PENDING:
                $this->messageManager->addNoticeMessage(
                    __('Sua conta está pendente de aprovação. Você pode navegar no site, mas não poderá realizar compras até que sua conta seja aprovada.')
                );
                break;
                
            case ApprovalStatus::STATUS_REJECTED:
                $this->messageManager->addWarningMessage(
                    __('Sua solicitação de cadastro foi recusada. Entre em contato conosco para mais informações.')
                );
                break;
                
            case ApprovalStatus::STATUS_SUSPENDED:
                $this->messageManager->addWarningMessage(
                    __('Sua conta está temporariamente suspensa. Entre em contato conosco para regularizar.')
                );
                break;
                
            case ApprovalStatus::STATUS_APPROVED:
                // Cliente aprovado - nada a fazer
                break;
        }
    }
}

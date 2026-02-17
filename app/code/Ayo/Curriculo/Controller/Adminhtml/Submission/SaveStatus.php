<?php
declare(strict_types=1);

namespace Ayo\Curriculo\Controller\Adminhtml\Submission;

use Ayo\Curriculo\Model\SubmissionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class SaveStatus extends Action
{
    public const ADMIN_RESOURCE = 'Ayo_Curriculo::submission_update';

    /**
     * @var SubmissionFactory
     */
    private $submissionFactory;

    public function __construct(
        Context $context,
        SubmissionFactory $submissionFactory
    ) {
        parent::__construct($context);
        $this->submissionFactory = $submissionFactory;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');
        
        if (!$id || !$status) {
            $this->messageManager->addErrorMessage(__('Parâmetros inválidos.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $submission = $this->submissionFactory->create();
            $submission->load($id);
            
            if (!$submission->getId()) {
                $this->messageManager->addErrorMessage(__('Candidatura não encontrada.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }

            $submission->setData('status', $status);
            $submission->save();
            
            $this->messageManager->addSuccessMessage(__('Status atualizado com sucesso.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Erro ao atualizar status: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/view', ['id' => $id]);
    }
}

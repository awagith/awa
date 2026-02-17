<?php
declare(strict_types=1);

namespace Ayo\Curriculo\Controller\Index;

use Ayo\Curriculo\Model\ResourceModel\Submission\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckStatus extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $trackingCode = trim((string)$this->getRequest()->getParam('tracking_code'));
        
        if ($trackingCode === '') {
            return $result->setData([
                'success' => false,
                'message' => __('Por favor, informe o código de acompanhamento.')
            ]);
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('tracking_code', $trackingCode);
        $submission = $collection->getFirstItem();
        
        if (!$submission->getId()) {
            return $result->setData([
                'success' => false,
                'message' => __('Candidatura não encontrada. Verifique o código informado.')
            ]);
        }

        $statusLabels = [
            'pending' => __('Pendente - Aguardando análise'),
            'reviewing' => __('Em Análise - Seu currículo está sendo avaliado'),
            'interview' => __('Entrevista - Você será contatado para agendar'),
            'approved' => __('Aprovado - Parabéns! Entre em contato conosco'),
            'rejected' => __('Não selecionado para esta vaga'),
        ];

        $status = $submission->getData('status') ?: 'pending';
        
        return $result->setData([
            'success' => true,
            'data' => [
                'tracking_code' => $submission->getData('tracking_code'),
                'name' => $submission->getData('name'),
                'position' => $submission->getData('position') ?: '-',
                'status' => $status,
                'status_label' => $statusLabels[$status] ?? $status,
                'submitted_at' => date('d/m/Y H:i', strtotime($submission->getData('created_at'))),
                'updated_at' => date('d/m/Y H:i', strtotime($submission->getData('updated_at'))),
            ]
        ]);
    }
}

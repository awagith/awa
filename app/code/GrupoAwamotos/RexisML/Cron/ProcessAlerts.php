<?php
/**
 * Cron para processar alertas automáticos de Churn e Cross-sell
 */
namespace GrupoAwamotos\RexisML\Cron;

use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory;
use GrupoAwamotos\RexisML\Helper\EmailNotifier;
use GrupoAwamotos\RexisML\Helper\WhatsAppNotifier;
use Psr\Log\LoggerInterface;

class ProcessAlerts
{
    protected $recomendacaoCollectionFactory;
    protected $emailNotifier;
    protected $whatsappNotifier;
    protected $logger;

    public function __construct(
        CollectionFactory $recomendacaoCollectionFactory,
        EmailNotifier $emailNotifier,
        WhatsAppNotifier $whatsappNotifier,
        LoggerInterface $logger
    ) {
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
        $this->emailNotifier = $emailNotifier;
        $this->whatsappNotifier = $whatsappNotifier;
        $this->logger = $logger;
    }

    /**
     * Processar alertas de Churn de alto valor (diário às 9h)
     */
    public function execute()
    {
        try {
            $this->logger->info('REXIS ML: Iniciando processamento de alertas automáticos');

            // 1. Buscar oportunidades de Churn com score alto (>= 0.85) e valor alto (>= R$ 500)
            $churnCollection = $this->recomendacaoCollectionFactory->create();
            $churnCollection->addFieldToFilter('classificacao_produto', 'Oportunidade Churn')
                           ->addFieldToFilter('pred', ['gteq' => 0.85])
                           ->addFieldToFilter('previsao_gasto_round_up', ['gteq' => 500])
                           ->setOrder('pred', 'DESC')
                           ->setPageSize(20);

            if ($churnCollection->getSize() > 0) {
                // Enviar email para equipe comercial
                $this->emailNotifier->sendChurnAlert($churnCollection);
                $this->logger->info(sprintf(
                    'REXIS ML: Email de Churn enviado com %d oportunidades',
                    $churnCollection->getSize()
                ));
            }

            // 2. Buscar oportunidades de Cross-sell de alto valor
            $crosssellCollection = $this->recomendacaoCollectionFactory->create();
            $crosssellCollection->addFieldToFilter('classificacao_produto', 'Oportunidade Cross-sell')
                               ->addFieldToFilter('pred', ['gteq' => 0.75])
                               ->addFieldToFilter('previsao_gasto_round_up', ['gteq' => 300])
                               ->setOrder('pred', 'DESC')
                               ->setPageSize(10);

            if ($crosssellCollection->getSize() > 0) {
                // Enviar notificação WhatsApp para vendedores
                $this->whatsappNotifier->sendCrosssellAlert($crosssellCollection);
                $this->logger->info(sprintf(
                    'REXIS ML: WhatsApp de Cross-sell enviado com %d oportunidades',
                    $crosssellCollection->getSize()
                ));
            }

            $this->logger->info('REXIS ML: Processamento de alertas concluído com sucesso');

        } catch (\Exception $e) {
            $this->logger->error('REXIS ML: Erro ao processar alertas - ' . $e->getMessage());
        }
    }
}

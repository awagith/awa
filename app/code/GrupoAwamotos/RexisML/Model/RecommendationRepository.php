<?php
/**
 * Implementação do Repositório de Recomendações
 */
namespace GrupoAwamotos\RexisML\Model;

use GrupoAwamotos\RexisML\Api\RecommendationRepositoryInterface;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory as RecomendacaoCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\NetworkRules\CollectionFactory as NetworkCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\CustomerClassification\CollectionFactory as RfmCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\MetricasConversao\CollectionFactory as MetricasCollectionFactory;
use GrupoAwamotos\RexisML\Model\MetricasConversaoFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class RecommendationRepository implements RecommendationRepositoryInterface
{
    protected $recomendacaoCollectionFactory;
    protected $networkCollectionFactory;
    protected $rfmCollectionFactory;
    protected $metricasCollectionFactory;
    protected $metricasFactory;

    public function __construct(
        RecomendacaoCollectionFactory $recomendacaoCollectionFactory,
        NetworkCollectionFactory $networkCollectionFactory,
        RfmCollectionFactory $rfmCollectionFactory,
        MetricasCollectionFactory $metricasCollectionFactory,
        MetricasConversaoFactory $metricasFactory
    ) {
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
        $this->networkCollectionFactory = $networkCollectionFactory;
        $this->rfmCollectionFactory = $rfmCollectionFactory;
        $this->metricasCollectionFactory = $metricasCollectionFactory;
        $this->metricasFactory = $metricasFactory;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomer(
        $customerId,
        $classificacao = null,
        $minScore = 0.7,
        $limit = 10
    ) {
        $collection = $this->recomendacaoCollectionFactory->create();
        $collection->addFieldToFilter('identificador_cliente', $customerId)
                   ->addFieldToFilter('pred', ['gteq' => $minScore])
                   ->setOrder('pred', 'DESC')
                   ->setPageSize($limit);

        if ($classificacao) {
            $collection->addFieldToFilter('classificacao_produto', $classificacao);
        }

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getCrosssellBySku(
        $sku,
        $minLift = 1.5,
        $limit = 10
    ) {
        $collection = $this->networkCollectionFactory->create();
        $collection->addFieldToFilter('antecedent', ['like' => '%' . $sku . '%'])
                   ->addFieldToFilter('lift', ['gteq' => $minLift])
                   ->setOrder('lift', 'DESC')
                   ->setPageSize($limit);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getRfmByCustomer($customerId)
    {
        $collection = $this->rfmCollectionFactory->create();
        $collection->addFieldToFilter('identificador_cliente', $customerId);

        $rfm = $collection->getFirstItem();
        if (!$rfm->getId()) {
            throw new NoSuchEntityException(
                __('Cliente %1 não encontrado na análise RFM', $customerId)
            );
        }

        return $rfm;
    }

    /**
     * @inheritDoc
     */
    public function registerConversion($chaveGlobal, $valorConversao)
    {
        try {
            $mesAtual = date('m-Y');

            // Buscar ou criar registro mensal
            $collection = $this->metricasCollectionFactory->create();
            $collection->addFieldToFilter('mes_rexis_code', $mesAtual);
            $metrica = $collection->getFirstItem();

            if (!$metrica->getId()) {
                $metrica = $this->metricasFactory->create();
                $metrica->setData('mes_rexis_code', $mesAtual);
                $metrica->setData('n_clientes_rec_mes_atual', 0);
                $metrica->setData('n_cliente_comprou_mes_atual', 0);
                $metrica->setData('n_produto_rec_mes_atual', 0);
                $metrica->setData('n_produto_comprou_mes_atual', 0);
                $metrica->setData('valor_esperado_atual', 0);
                $metrica->setData('valor_convertido_atual', 0);
            }

            // Incrementar conversao
            $metrica->setData(
                'n_cliente_comprou_mes_atual',
                (int)$metrica->getData('n_cliente_comprou_mes_atual') + 1
            );
            $metrica->setData(
                'valor_convertido_atual',
                (float)$metrica->getData('valor_convertido_atual') + $valorConversao
            );

            // Recalcular percentual
            $recomendados = (int)$metrica->getData('n_clientes_rec_mes_atual');
            if ($recomendados > 0) {
                $metrica->setData(
                    'perc_conversao_cliente',
                    (int)$metrica->getData('n_cliente_comprou_mes_atual') / $recomendados
                );
            }

            $metrica->save();

            // Atualizar registro na tabela de recomendacoes
            $recomCollection = $this->recomendacaoCollectionFactory->create();
            $recomCollection->addFieldToFilter('chave_global', $chaveGlobal);
            $recomendacao = $recomCollection->getFirstItem();
            if ($recomendacao->getId()) {
                $recomendacao->setData('valor_convertida', $valorConversao);
                $recomendacao->setData('quantidade_convertida', 1);
                $recomendacao->save();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetrics()
    {
        $recomendacaoCollection = $this->recomendacaoCollectionFactory->create();
        $metricasCollection = $this->metricasCollectionFactory->create();

        // Estatísticas gerais
        $totalRecomendacoes = $recomendacaoCollection->getSize();

        $churnCollection = $this->recomendacaoCollectionFactory->create();
        $churnCollection->addFieldToFilter('classificacao_produto', 'Oportunidade Churn');
        $oportunidadesChurn = $churnCollection->getSize();

        $crosssellCollection = $this->recomendacaoCollectionFactory->create();
        $crosssellCollection->addFieldToFilter('classificacao_produto', 'Oportunidade Cross-sell');
        $oportunidadesCrosssell = $crosssellCollection->getSize();

        // Valor potencial (soma de previsão de gasto)
        $recomendacaoCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $recomendacaoCollection->getSelect()->columns([
            'valor_potencial' => new \Zend_Db_Expr('SUM(previsao_gasto_round_up)'),
            'clientes' => new \Zend_Db_Expr('COUNT(DISTINCT identificador_cliente)'),
            'produtos' => new \Zend_Db_Expr('COUNT(DISTINCT identificador_produto)'),
            'score_medio' => new \Zend_Db_Expr('AVG(pred)')
        ]);
        $stats = $recomendacaoCollection->getFirstItem();

        // Taxa de conversão (baseada em métricas mensais)
        $metricasCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $metricasCollection->getSelect()->columns([
            'total_recomendados' => new \Zend_Db_Expr('SUM(n_clientes_rec_mes_atual)'),
            'total_compraram' => new \Zend_Db_Expr('SUM(n_cliente_comprou_mes_atual)')
        ]);
        $metricasStats = $metricasCollection->getFirstItem();
        $totalRecomendados = (int)$metricasStats->getData('total_recomendados');
        $totalCompraram = (int)$metricasStats->getData('total_compraram');
        $taxaConversao = $totalRecomendados > 0 ? ($totalCompraram / $totalRecomendados) * 100 : 0;

        return new \Magento\Framework\DataObject([
            'total_recomendacoes' => $totalRecomendacoes,
            'oportunidades_churn' => $oportunidadesChurn,
            'oportunidades_crosssell' => $oportunidadesCrosssell,
            'valor_potencial' => (float)$stats->getData('valor_potencial'),
            'clientes_analisados' => (int)$stats->getData('clientes'),
            'produtos_recomendados' => (int)$stats->getData('produtos'),
            'score_medio' => (float)$stats->getData('score_medio'),
            'taxa_conversao' => round($taxaConversao, 2)
        ]);
    }
}

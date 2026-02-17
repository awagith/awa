<?php
/**
 * Comando CLI para exibir estatísticas do REXIS ML
 */
namespace GrupoAwamotos\RexisML\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory as RecomendacaoCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\NetworkRules\CollectionFactory as NetworkCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\CustomerClassification\CollectionFactory as RfmCollectionFactory;
use GrupoAwamotos\RexisML\Model\ResourceModel\MetricasConversao\CollectionFactory as MetricasCollectionFactory;

class StatsCommand extends Command
{
    protected $recomendacaoCollectionFactory;
    protected $networkCollectionFactory;
    protected $rfmCollectionFactory;
    protected $metricasCollectionFactory;

    public function __construct(
        RecomendacaoCollectionFactory $recomendacaoCollectionFactory,
        NetworkCollectionFactory $networkCollectionFactory,
        RfmCollectionFactory $rfmCollectionFactory,
        MetricasCollectionFactory $metricasCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
        $this->networkCollectionFactory = $networkCollectionFactory;
        $this->rfmCollectionFactory = $rfmCollectionFactory;
        $this->metricasCollectionFactory = $metricasCollectionFactory;
    }

    protected function configure()
    {
        $this->setName('rexis:stats')
            ->setDescription('Exibir estatísticas do sistema REXIS ML');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<fg=cyan;options=bold>╔════════════════════════════════════════════╗</>');
        $output->writeln('<fg=cyan;options=bold>║     REXIS ML - Estatísticas do Sistema    ║</>');
        $output->writeln('<fg=cyan;options=bold>╚════════════════════════════════════════════╝</>');
        $output->writeln('');

        // 1. Estatísticas Gerais
        $this->showGeneralStats($output);

        // 2. Distribuição por Classificação
        $this->showClassificationDistribution($output);

        // 3. Top Oportunidades de Churn
        $this->showTopChurn($output);

        // 4. Top Regras de Cross-sell
        $this->showTopCrosssell($output);

        // 5. Segmentos RFM
        $this->showRfmSegments($output);

        // 6. Métricas de Conversão
        $this->showConversionMetrics($output);

        $output->writeln('');
        $output->writeln('<info>Estatísticas geradas em: ' . date('d/m/Y H:i:s') . '</info>');
        $output->writeln('');

        return Command::SUCCESS;
    }

    protected function showGeneralStats(OutputInterface $output)
    {
        $recomendacoes = $this->recomendacaoCollectionFactory->create();
        $total = $recomendacoes->getSize();

        $recomendacoes->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $recomendacoes->getSelect()->columns([
            'clientes' => new \Zend_Db_Expr('COUNT(DISTINCT identificador_cliente)'),
            'produtos' => new \Zend_Db_Expr('COUNT(DISTINCT identificador_produto)'),
            'score_medio' => new \Zend_Db_Expr('AVG(pred)'),
            'valor_total' => new \Zend_Db_Expr('SUM(previsao_gasto_round_up)')
        ]);
        $stats = $recomendacoes->getFirstItem();

        $output->writeln('<fg=yellow;options=bold>📊 ESTATÍSTICAS GERAIS</>');
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Métrica', 'Valor']);
        $table->setRows([
            ['Total de Recomendações', number_format($total, 0, ',', '.')],
            ['Clientes Analisados', number_format($stats->getData('clientes'), 0, ',', '.')],
            ['Produtos Recomendados', number_format($stats->getData('produtos'), 0, ',', '.')],
            ['Score Médio ML', number_format($stats->getData('score_medio') * 100, 1) . '%'],
            ['Valor Potencial', 'R$ ' . number_format($stats->getData('valor_total'), 2, ',', '.')]
        ]);
        $table->render();
        $output->writeln('');
    }

    protected function showClassificationDistribution(OutputInterface $output)
    {
        $output->writeln('<fg=yellow;options=bold>📈 DISTRIBUIÇÃO POR CLASSIFICAÇÃO</>');
        $output->writeln('');

        $collection = $this->recomendacaoCollectionFactory->create();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'classificacao_produto',
            'total' => new \Zend_Db_Expr('COUNT(*)'),
            'valor_total' => new \Zend_Db_Expr('SUM(previsao_gasto_round_up)'),
            'score_medio' => new \Zend_Db_Expr('AVG(pred)')
        ]);
        $collection->getSelect()->group('classificacao_produto');
        $collection->getSelect()->order('total DESC');

        $table = new Table($output);
        $table->setHeaders(['Classificação', 'Quantidade', 'Valor Total', 'Score Médio']);

        $rows = [];
        foreach ($collection as $item) {
            $rows[] = [
                $item->getData('classificacao_produto'),
                number_format($item->getData('total'), 0, ',', '.'),
                'R$ ' . number_format($item->getData('valor_total'), 2, ',', '.'),
                number_format($item->getData('score_medio') * 100, 1) . '%'
            ];
        }
        $table->setRows($rows);
        $table->render();
        $output->writeln('');
    }

    protected function showTopChurn(OutputInterface $output)
    {
        $output->writeln('<fg=red;options=bold>🚨 TOP 10 OPORTUNIDADES DE CHURN</>');
        $output->writeln('');

        $collection = $this->recomendacaoCollectionFactory->create();
        $collection->addFieldToFilter('classificacao_produto', 'Oportunidade Churn')
                   ->setOrder('pred', 'DESC')
                   ->setPageSize(10);

        if ($collection->getSize() === 0) {
            $output->writeln('<comment>Nenhuma oportunidade de churn encontrada.</comment>');
            $output->writeln('');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Cliente', 'Produto', 'Score', 'Valor Previsto', 'Recência']);

        $rows = [];
        foreach ($collection as $item) {
            $rows[] = [
                '#' . $item->getIdentificadorCliente(),
                $item->getIdentificadorProduto(),
                number_format($item->getPred() * 100, 1) . '%',
                'R$ ' . number_format($item->getPrevisaoGastoRoundUp(), 2, ',', '.'),
                $item->getRecencia() . ' dias'
            ];
        }
        $table->setRows($rows);
        $table->render();
        $output->writeln('');
    }

    protected function showTopCrosssell(OutputInterface $output)
    {
        $output->writeln('<fg=green;options=bold>💡 TOP 10 REGRAS DE CROSS-SELL</>');
        $output->writeln('');

        $collection = $this->networkCollectionFactory->create();
        $collection->setOrder('lift', 'DESC')
                   ->setPageSize(10);

        if ($collection->getSize() === 0) {
            $output->writeln('<comment>Nenhuma regra de cross-sell encontrada.</comment>');
            $output->writeln('');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Produto A', 'Produto B', 'Lift', 'Confidence', 'Support']);

        $rows = [];
        foreach ($collection as $item) {
            $rows[] = [
                $item->getAntecedent(),
                $item->getConsequent(),
                number_format($item->getLift(), 2),
                number_format($item->getConfidence() * 100, 1) . '%',
                number_format($item->getSupport() * 100, 2) . '%'
            ];
        }
        $table->setRows($rows);
        $table->render();
        $output->writeln('');
    }

    protected function showRfmSegments(OutputInterface $output)
    {
        $output->writeln('<fg=magenta;options=bold>👥 SEGMENTOS RFM</>');
        $output->writeln('');

        $collection = $this->rfmCollectionFactory->create();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'classificacao_cliente',
            'total' => new \Zend_Db_Expr('COUNT(*)'),
            'valor_total' => new \Zend_Db_Expr('SUM(monetary)')
        ]);
        $collection->getSelect()->group('classificacao_cliente');
        $collection->getSelect()->order('total DESC');

        if ($collection->getSize() === 0) {
            $output->writeln('<comment>Nenhum segmento RFM encontrado.</comment>');
            $output->writeln('');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Segmento', 'Clientes', 'Valor Total']);

        $rows = [];
        foreach ($collection as $item) {
            $rows[] = [
                $item->getData('classificacao_cliente') ?: 'Não classificado',
                number_format($item->getData('total'), 0, ',', '.'),
                'R$ ' . number_format($item->getData('valor_total'), 2, ',', '.')
            ];
        }
        $table->setRows($rows);
        $table->render();
        $output->writeln('');
    }

    protected function showConversionMetrics(OutputInterface $output)
    {
        $output->writeln('<fg=blue;options=bold>📊 MÉTRICAS DE CONVERSÃO</>');
        $output->writeln('');

        $metricas = $this->metricasCollectionFactory->create();
        $metricas->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $metricas->getSelect()->columns([
            'total_recomendados' => new \Zend_Db_Expr('SUM(n_clientes_rec_mes_atual)'),
            'total_compraram' => new \Zend_Db_Expr('SUM(n_cliente_comprou_mes_atual)'),
            'valor_esperado' => new \Zend_Db_Expr('SUM(valor_esperado_atual)'),
            'valor_convertido' => new \Zend_Db_Expr('SUM(valor_convertido_atual)')
        ]);
        $stats = $metricas->getFirstItem();

        $totalRecomendados = (int)$stats->getData('total_recomendados');
        $totalCompraram = (int)$stats->getData('total_compraram');
        $valorEsperado = (float)$stats->getData('valor_esperado');
        $valorConvertido = (float)$stats->getData('valor_convertido');
        $taxaConversao = $totalRecomendados > 0 ? ($totalCompraram / $totalRecomendados) * 100 : 0;

        $table = new Table($output);
        $table->setHeaders(['Métrica', 'Valor']);
        $table->setRows([
            ['Clientes Recomendados', number_format($totalRecomendados, 0, ',', '.')],
            ['Clientes que Compraram', number_format($totalCompraram, 0, ',', '.')],
            ['Taxa de Conversão', number_format($taxaConversao, 2) . '%'],
            ['Valor Esperado', 'R$ ' . number_format($valorEsperado, 2, ',', '.')],
            ['Valor Convertido', 'R$ ' . number_format($valorConvertido, 2, ',', '.')]
        ]);
        $table->render();
        $output->writeln('');
    }
}

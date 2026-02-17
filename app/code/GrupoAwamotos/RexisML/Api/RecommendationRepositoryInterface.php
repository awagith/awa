<?php
/**
 * Interface para API de Recomendações REXIS ML
 */
namespace GrupoAwamotos\RexisML\Api;

interface RecommendationRepositoryInterface
{
    /**
     * Obter recomendações para um cliente específico
     *
     * @param int $customerId
     * @param string|null $classificacao Filtrar por classificação (Churn, Cross-sell, etc)
     * @param float $minScore Score mínimo (0-1)
     * @param int $limit Limite de resultados
     * @return \GrupoAwamotos\RexisML\Api\Data\RecommendationInterface[]
     */
    public function getByCustomer(
        $customerId,
        $classificacao = null,
        $minScore = 0.7,
        $limit = 10
    );

    /**
     * Obter oportunidades de cross-sell para um produto
     *
     * @param string $sku
     * @param float $minLift Lift mínimo
     * @param int $limit Limite de resultados
     * @return \GrupoAwamotos\RexisML\Api\Data\CrosssellInterface[]
     */
    public function getCrosssellBySku(
        $sku,
        $minLift = 1.5,
        $limit = 10
    );

    /**
     * Obter classificação RFM de um cliente
     *
     * @param int $customerId
     * @return \GrupoAwamotos\RexisML\Api\Data\RfmInterface
     */
    public function getRfmByCustomer($customerId);

    /**
     * Registrar conversão de uma recomendação
     *
     * @param string $chaveGlobal
     * @param float $valorConversao
     * @return bool
     */
    public function registerConversion($chaveGlobal, $valorConversao);

    /**
     * Obter métricas gerais do sistema
     *
     * @return \GrupoAwamotos\RexisML\Api\Data\MetricsInterface
     */
    public function getMetrics();
}

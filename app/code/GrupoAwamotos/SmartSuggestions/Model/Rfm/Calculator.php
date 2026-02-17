<?php
declare(strict_types=1);

namespace GrupoAwamotos\SmartSuggestions\Model\Rfm;

use GrupoAwamotos\SmartSuggestions\Api\RfmCalculatorInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * RFM Calculator
 *
 * Calculates Recency, Frequency, Monetary scores for customer segmentation
 * R = Days since last purchase (lower is better)
 * F = Number of orders (higher is better)
 * M = Total revenue (higher is better)
 */
class Calculator implements RfmCalculatorInterface
{
    private const ANALYSIS_PERIOD_MONTHS = 24;

    private ConnectionInterface $connection;
    private LoggerInterface $logger;
    private ?array $cachedResults = null;

    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function calculateAll(): array
    {
        if ($this->cachedResults !== null) {
            return $this->cachedResults;
        }

        try {
            // Get raw RFM data from ERP
            $rawData = $this->getRawRfmData();

            if (empty($rawData)) {
                return [];
            }

            // Extract values for quintile calculation
            $recencyValues = array_column($rawData, 'recency');
            $frequencyValues = array_column($rawData, 'frequency');
            $monetaryValues = array_column($rawData, 'monetary');

            // Calculate quintiles
            $rQuintiles = $this->calculateQuintiles($recencyValues);
            $fQuintiles = $this->calculateQuintiles($frequencyValues);
            $mQuintiles = $this->calculateQuintiles($monetaryValues);

            // Assign scores to each customer
            $scoredCustomers = [];
            foreach ($rawData as $customer) {
                // For recency, lower is better, so we invert the score
                $rScore = 6 - $this->getQuintileScore((float)$customer['recency'], $rQuintiles);
                $fScore = $this->getQuintileScore((float)$customer['frequency'], $fQuintiles);
                $mScore = $this->getQuintileScore((float)$customer['monetary'], $mQuintiles);

                $segment = $this->determineSegment($rScore, $fScore, $mScore);

                $scoredCustomers[] = [
                    'customer_id' => (int)$customer['customer_id'],
                    'customer_name' => $customer['customer_name'],
                    'trade_name' => $customer['trade_name'],
                    'cnpj' => $customer['cnpj'],
                    'city' => $customer['city'],
                    'state' => $customer['state'],
                    'recency_days' => (int)$customer['recency'],
                    'frequency' => (int)$customer['frequency'],
                    'monetary' => (float)$customer['monetary'],
                    'r_score' => $rScore,
                    'f_score' => $fScore,
                    'm_score' => $mScore,
                    'rfm_score' => "{$rScore}{$fScore}{$mScore}",
                    'rfm_total' => $rScore + $fScore + $mScore,
                    'segment' => $segment,
                    'last_purchase' => $customer['last_purchase'] ?? null
                ];
            }

            // Sort by RFM total score descending
            usort($scoredCustomers, function ($a, $b) {
                return $b['rfm_total'] <=> $a['rfm_total'];
            });

            $this->cachedResults = $scoredCustomers;
            return $scoredCustomers;

        } catch (\Exception $e) {
            $this->logger->error('RFM Calculation Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function calculateForCustomer(int $customerId): ?array
    {
        $allCustomers = $this->calculateAll();

        foreach ($allCustomers as $customer) {
            if ($customer['customer_id'] === $customerId) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSegmentStatistics(): array
    {
        $customers = $this->calculateAll();
        $stats = [];

        foreach ($customers as $customer) {
            $segment = $customer['segment'];

            if (!isset($stats[$segment])) {
                $stats[$segment] = [
                    'segment' => $segment,
                    'count' => 0,
                    'total_revenue' => 0,
                    'total_orders' => 0,
                    'total_recency' => 0,
                    'color' => $this->getSegmentColor($segment),
                    'priority' => $this->getSegmentPriority($segment)
                ];
            }

            $stats[$segment]['count']++;
            $stats[$segment]['total_revenue'] += $customer['monetary'];
            $stats[$segment]['total_orders'] += $customer['frequency'];
            $stats[$segment]['total_recency'] += $customer['recency_days'];
        }

        // Calculate averages
        foreach ($stats as $segment => &$data) {
            if ($data['count'] > 0) {
                $data['avg_revenue'] = $data['total_revenue'] / $data['count'];
                $data['avg_orders'] = $data['total_orders'] / $data['count'];
                $data['avg_recency'] = $data['total_recency'] / $data['count'];
                $data['avg_order_value'] = $data['total_orders'] > 0
                    ? $data['total_revenue'] / $data['total_orders']
                    : 0;
            }
        }

        // Sort by priority
        uasort($stats, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return array_values($stats);
    }

    /**
     * @inheritdoc
     */
    public function getCustomersBySegment(string $segment, int $limit = 100): array
    {
        $customers = $this->calculateAll();

        $filtered = array_filter($customers, fn($c) => $c['segment'] === $segment);

        // Sort by monetary value descending
        usort($filtered, fn($a, $b) => $b['monetary'] <=> $a['monetary']);

        return array_slice($filtered, 0, $limit);
    }

    /**
     * @inheritdoc
     */
    public function getRecommendations(string $segment): array
    {
        $recommendations = [
            'Champions' => [
                'action' => 'Recompensar e manter',
                'priority' => 'Alta',
                'strategies' => [
                    'Acesso antecipado a novos produtos',
                    'Programa VIP de fidelidade',
                    'Programa de indicação com recompensas premium',
                    'Gerente de conta dedicado'
                ],
                'channels' => ['WhatsApp', 'Email', 'Telefone'],
                'discount_range' => '5-10%'
            ],
            'Loyal' => [
                'action' => 'Upsell e cross-sell',
                'priority' => 'Alta',
                'strategies' => [
                    'Recomendar produtos de maior valor',
                    'Ofertas de pacotes/combos',
                    'Upgrades no programa de fidelidade',
                    'Solicitar avaliações e depoimentos'
                ],
                'channels' => ['WhatsApp', 'Email'],
                'discount_range' => '10-15%'
            ],
            'Potential Loyalist' => [
                'action' => 'Converter para fidelidade',
                'priority' => 'Média-Alta',
                'strategies' => [
                    'Convite para programa de fidelidade',
                    'Recomendações baseadas no histórico',
                    'Engajar em múltiplos canais'
                ],
                'channels' => ['WhatsApp', 'Email'],
                'discount_range' => '10-15%'
            ],
            'New Customers' => [
                'action' => 'Nurturing e educação',
                'priority' => 'Média',
                'strategies' => [
                    'Série de emails de boas-vindas',
                    'Mostrar catálogo completo',
                    'Oferta especial segunda compra'
                ],
                'channels' => ['Email', 'WhatsApp'],
                'discount_range' => '15-20%'
            ],
            'Promising' => [
                'action' => 'Aumentar engajamento',
                'priority' => 'Média',
                'strategies' => [
                    'Produtos relacionados aos já comprados',
                    'Ofertas por tempo limitado',
                    'Comunicação mais frequente'
                ],
                'channels' => ['WhatsApp', 'Email'],
                'discount_range' => '10-15%'
            ],
            'Need Attention' => [
                'action' => 'Reativar interesse',
                'priority' => 'Média-Alta',
                'strategies' => [
                    'Oferta especial personalizada',
                    'Destacar novidades desde última compra',
                    'Verificar satisfação'
                ],
                'channels' => ['WhatsApp', 'Telefone'],
                'discount_range' => '15-20%'
            ],
            'At Risk' => [
                'action' => 'Reativar urgentemente',
                'priority' => 'Alta',
                'strategies' => [
                    'Campanha de win-back por email',
                    'Desconto especial em produtos já comprados',
                    'Pesquisa para entender motivos',
                    'Ofertas exclusivas por tempo limitado'
                ],
                'channels' => ['WhatsApp', 'Telefone', 'Email'],
                'discount_range' => '20-30%'
            ],
            "Can't Lose" => [
                'action' => 'Atenção imediata necessária',
                'priority' => 'Crítica',
                'strategies' => [
                    'Contato pessoal da equipe de vendas',
                    'Oferta exclusiva de reativação',
                    'Entender e resolver problemas',
                    'Oferecer suporte premium'
                ],
                'channels' => ['Telefone', 'WhatsApp'],
                'discount_range' => '25-35%'
            ],
            'Hibernating' => [
                'action' => 'Tentar reativar',
                'priority' => 'Baixa',
                'strategies' => [
                    'Campanha de reativação em massa',
                    'Mostrar novos produtos',
                    'Oferta agressiva'
                ],
                'channels' => ['Email'],
                'discount_range' => '20-30%'
            ],
            'Lost' => [
                'action' => 'Tentar recuperar',
                'priority' => 'Baixa',
                'strategies' => [
                    'Campanha agressiva de win-back',
                    'Pesquisa de feedback',
                    'Considerar custo-benefício da reativação'
                ],
                'channels' => ['Email'],
                'discount_range' => '30-40%'
            ]
        ];

        return $recommendations[$segment] ?? [
            'action' => 'Engajamento geral',
            'priority' => 'Média',
            'strategies' => ['Comunicação regular de marketing'],
            'channels' => ['Email'],
            'discount_range' => '10-15%'
        ];
    }

    /**
     * Get raw RFM data from ERP
     */
    private function getRawRfmData(): array
    {
        $sql = "
            SELECT
                f.CODIGO as customer_id,
                f.RAZAO as customer_name,
                f.FANTASIA as trade_name,
                f.CGC as cnpj,
                f.CIDADE as city,
                f.UF as state,
                DATEDIFF(DAY, MAX(p.DTPEDIDO), GETDATE()) as recency,
                COUNT(DISTINCT p.CODIGO) as frequency,
                SUM(i.VLRTOTAL) as monetary,
                MAX(p.DTPEDIDO) as last_purchase
            FROM FN_FORNECEDORES f
            INNER JOIN VE_PEDIDO p ON f.CODIGO = p.CLIENTE
            INNER JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
            WHERE f.CKCLIENTE = 'S'
              AND p.STATUS NOT IN ('C', 'X')
              AND p.DTPEDIDO >= DATEADD(MONTH, -" . self::ANALYSIS_PERIOD_MONTHS . ", GETDATE())
            GROUP BY f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CIDADE, f.UF
            HAVING COUNT(DISTINCT p.CODIGO) > 0
        ";

        return $this->connection->query($sql);
    }

    /**
     * Calculate quintile boundaries for a set of values
     */
    private function calculateQuintiles(array $values): array
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
        }

        return [
            'q1' => $values[(int)($count * 0.2)] ?? 0,
            'q2' => $values[(int)($count * 0.4)] ?? 0,
            'q3' => $values[(int)($count * 0.6)] ?? 0,
            'q4' => $values[(int)($count * 0.8)] ?? 0
        ];
    }

    /**
     * Get quintile score (1-5) for a value
     */
    private function getQuintileScore(float $value, array $quintiles): int
    {
        if ($value <= $quintiles['q1']) return 1;
        if ($value <= $quintiles['q2']) return 2;
        if ($value <= $quintiles['q3']) return 3;
        if ($value <= $quintiles['q4']) return 4;
        return 5;
    }

    /**
     * Determine customer segment based on RFM scores
     */
    private function determineSegment(int $r, int $f, int $m): string
    {
        // Champions: High R, High F, High M
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }

        // Loyal: Good R, Good F, Good M
        if ($r >= 3 && $f >= 3 && $m >= 3) {
            return 'Loyal';
        }

        // Potential Loyalist: High R, Medium F/M
        if ($r >= 4 && ($f >= 2 || $m >= 2)) {
            return 'Potential Loyalist';
        }

        // New Customers: High R, Low F
        if ($r >= 4 && $f <= 2) {
            return 'New Customers';
        }

        // Promising: Medium-High R, Low-Medium F/M
        if ($r >= 3 && $f <= 3 && $m <= 3) {
            return 'Promising';
        }

        // Need Attention: Medium R, Medium F, Medium M
        if ($r >= 2 && $r <= 3 && $f >= 2 && $m >= 2) {
            return 'Need Attention';
        }

        // Can't Lose: Low R but High F and M (valuable customers leaving)
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return "Can't Lose";
        }

        // At Risk: Low R, Medium-High F/M
        if ($r <= 2 && ($f >= 3 || $m >= 3)) {
            return 'At Risk';
        }

        // Hibernating: Low R, Low-Medium F/M
        if ($r <= 2 && $f >= 2 && $m >= 2) {
            return 'Hibernating';
        }

        // Lost: Low R, Low F, Low M
        if ($r <= 2 && $f <= 2) {
            return 'Lost';
        }

        return 'Other';
    }

    /**
     * Get color for segment visualization
     */
    private function getSegmentColor(string $segment): string
    {
        $colors = [
            'Champions' => '#00E396',
            'Loyal' => '#008FFB',
            'Potential Loyalist' => '#00D9E9',
            'New Customers' => '#775DD0',
            'Promising' => '#26A0FC',
            'Need Attention' => '#FEB019',
            'At Risk' => '#FF4560',
            "Can't Lose" => '#FF6178',
            'Hibernating' => '#A5978B',
            'Lost' => '#546E7A',
            'Other' => '#999999'
        ];

        return $colors[$segment] ?? '#999999';
    }

    /**
     * Get segment priority for sorting
     */
    private function getSegmentPriority(string $segment): int
    {
        $priorities = [
            'Champions' => 1,
            'Loyal' => 2,
            "Can't Lose" => 3,
            'At Risk' => 4,
            'Potential Loyalist' => 5,
            'Need Attention' => 6,
            'New Customers' => 7,
            'Promising' => 8,
            'Hibernating' => 9,
            'Lost' => 10,
            'Other' => 11
        ];

        return $priorities[$segment] ?? 99;
    }
}

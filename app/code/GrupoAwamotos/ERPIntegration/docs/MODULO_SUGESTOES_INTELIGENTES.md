# Módulo de Sugestões Inteligentes - Especificação Técnica
## GrupoAwamotos ERP Integration - Smart Suggestions Module

**Status: IMPLEMENTADO**
**Versão: 2.0**
**Última atualização: 30/01/2026**

---

## 1. Visão Geral

### Objetivo
Módulo completo no admin do Magento 2 que utiliza os dados do ERP SQL Server para:
- Sugerir produtos/carrinhos personalizados por cliente
- Projetar vendas e fechamento de mês com simulação Monte Carlo
- Dashboard com gráficos ApexCharts e KPIs em tempo real
- Análise RFM para segmentação de clientes (11 segmentos)
- Geração automática de cupons por segmento
- Alertas por email para clientes em risco

### Dados Utilizados do ERP
```sql
-- Clientes/Fornecedores
FN_FORNECEDORES: CODIGO, RAZAO, FANTASIA, CGC, CIDADE, UF, CKCLIENTE

-- Pedidos
VE_PEDIDO: CODIGO, CLIENTE, DTPEDIDO, STATUS, VENDEDOR

-- Itens dos Pedidos
VE_PEDIDOITENS: PEDIDO, PRODUTO, QUANTIDADE, VLRUNITARIO, VLRTOTAL
```

---

## 2. Arquitetura Implementada

### Estrutura de Diretórios Atual

```
app/code/GrupoAwamotos/ERPIntegration/
├── Api/
│   ├── SuggestedCartInterface.php         # Interface REST para carrinho sugerido
│   ├── RfmAnalysisInterface.php           # Interface REST para análise RFM
│   └── ForecastInterface.php              # Interface REST para projeções
├── Block/
│   ├── Adminhtml/
│   │   └── Dashboard.php                  # Block principal do dashboard
│   └── Customer/
│       └── Suggestions.php                # Block de sugestões no frontend
├── Controller/
│   ├── Adminhtml/
│   │   └── Dashboard/
│   │       └── Index.php                  # Controller do dashboard admin
│   └── Cart/
│       └── AddSuggested.php               # Controller para adicionar itens sugeridos
├── Model/
│   ├── Api/
│   │   ├── SuggestedCartManagement.php    # Implementação API carrinho sugerido
│   │   ├── RfmAnalysisManagement.php      # Implementação API RFM
│   │   └── ForecastManagement.php         # Implementação API projeções
│   ├── Cart/
│   │   └── SuggestedCart.php              # Motor de sugestões (545 linhas)
│   ├── Forecast/
│   │   └── SalesProjection.php            # Motor de projeções Monte Carlo (669 linhas)
│   ├── Rfm/
│   │   └── Calculator.php                 # Calculadora RFM 11 segmentos (423 linhas)
│   ├── Coupon/
│   │   └── Generator.php                  # Gerador de cupons por segmento (316 linhas)
│   ├── Alert/
│   │   └── EmailSender.php                # Alertas por email (410 linhas)
│   ├── CustomerSync.php                   # Sincronização de clientes ERP
│   ├── OrderSync.php                      # Sincronização de pedidos ERP
│   ├── StockSync.php                      # Sincronização de estoque ERP
│   └── PurchaseHistory.php                # Histórico de compras do cliente
├── Cron/
│   ├── Optimize.php                       # Cron de otimização
│   └── Scan.php                           # Cron de varredura
├── Helper/
│   └── Data.php                           # Helper principal com conexão SQL Server
├── view/
│   ├── adminhtml/
│   │   ├── layout/
│   │   │   └── erpintegration_dashboard_index.xml
│   │   └── templates/
│   │       └── dashboard.phtml            # Dashboard com ApexCharts (1000 linhas)
│   └── frontend/
│       └── templates/
│           └── customer/
│               └── suggestions.phtml      # Widget de sugestões frontend
└── etc/
    ├── module.xml                         # Declaração do módulo
    ├── di.xml                             # Injeção de dependências (147 linhas)
    ├── acl.xml                            # Permissões admin (7 recursos)
    ├── webapi.xml                         # 12 endpoints REST API
    ├── adminhtml/
    │   ├── menu.xml                       # Menu admin
    │   ├── routes.xml                     # Rotas admin
    │   └── system.xml                     # Configurações do módulo
    └── crontab.xml                        # Jobs agendados
```

---

## 3. Funcionalidades Implementadas

### 3.1 Dashboard Principal com ApexCharts

**Arquivo:** `view/adminhtml/templates/dashboard.phtml`

#### KPIs em Tempo Real
- Total de clientes ativos
- Pedidos do mês
- Receita do mês
- Clientes em risco

#### Gráficos Implementados

1. **Gráfico de Área - Vendas vs Projeção**
```javascript
// Implementado com ApexCharts
var options = {
    chart: { type: 'area', height: 350, zoom: { enabled: true } },
    series: [
        { name: 'Vendas Reais', data: salesData },
        { name: 'Projeção', data: forecastData }
    ],
    stroke: { curve: 'smooth', dashArray: [0, 5] },
    fill: { type: 'gradient' },
    colors: ['#00E396', '#FEB019'],
    annotations: {
        xaxis: [{
            x: todayTimestamp,
            borderColor: '#775DD0',
            label: { text: 'Hoje' }
        }]
    }
};
```

2. **Donut Chart - Segmentos RFM**
```javascript
var rfmOptions = {
    chart: { type: 'donut', height: 300 },
    series: segmentCounts,
    labels: segmentNames,
    colors: ['#00E396', '#008FFB', '#775DD0', '#FEB019', '#FF4560', ...]
};
```

#### Banner de Projeção
- Receita realizada vs meta
- Barra de progresso visual
- Projeção pessimista/realista/otimista
- Meta diária calculada

#### Tabelas
- Top 10 clientes por receita
- Clientes em risco (At Risk)
- Cards de segmentos RFM com ações

---

### 3.2 Sistema de Sugestão de Carrinhos

**Arquivo:** `Model/Cart/SuggestedCart.php` (545 linhas)

#### Algoritmos Implementados

1. **Reorder Suggestions** - Baseado em ciclo de compra
```php
// Calcula ciclo médio de recompra por produto/cliente
WITH CycleCTE AS (
    SELECT
        i.PRODUTO,
        p.DTPEDIDO,
        LAG(p.DTPEDIDO) OVER (PARTITION BY i.PRODUTO ORDER BY p.DTPEDIDO) as prev_date,
        DATEDIFF(DAY, LAG(p.DTPEDIDO) OVER (...), p.DTPEDIDO) as cycle_days
    FROM VE_PEDIDOITENS i
    ...
)
```

2. **Cross-Sell** - Produtos frequentemente comprados juntos
```php
// Association rules: Se comprou A, também comprou B
SELECT TOP 8 other_product, COUNT(*) as frequency
FROM orders WHERE product IN (customer_products)
GROUP BY other_product
ORDER BY frequency DESC
```

3. **Collaborative Filtering** - Clientes similares
```php
// Jaccard Similarity entre clientes
// Sugere produtos de clientes com histórico similar
```

#### Estrutura do Carrinho Sugerido
```php
return [
    'customer_code' => $customerCode,
    'customer_name' => $customerName,
    'generated_at' => date('Y-m-d H:i:s'),
    'sections' => [
        [
            'type' => 'reorder',
            'title' => 'Hora de Repor',
            'icon' => 'refresh',
            'items' => [...],
            'subtotal' => 1234.56
        ],
        [
            'type' => 'cross_sell',
            'title' => 'Você Também Pode Gostar',
            'icon' => 'lightbulb',
            'items' => [...],
            'subtotal' => 567.89
        ],
        [
            'type' => 'similar_customers',
            'title' => 'Clientes Como Você Compraram',
            'icon' => 'users',
            'items' => [...],
            'subtotal' => 890.12
        ]
    ],
    'totals' => [
        'items_count' => 15,
        'subtotal' => 2692.57,
        'potential_savings' => 134.63
    ]
];
```

---

### 3.3 Projeção de Vendas com Monte Carlo

**Arquivo:** `Model/Forecast/SalesProjection.php` (669 linhas)

#### Algoritmos Implementados

1. **Média Móvel Ponderada**
```php
// Últimos 3 meses com pesos decrescentes
$weights = [0.5, 0.3, 0.2];
```

2. **Análise de Tendência Linear**
```php
// Regressão linear nos últimos 12 meses
$slope = calculateLinearRegression($historicalData);
```

3. **Índice de Sazonalidade**
```php
// Calcula fator sazonal por mês
$seasonalIndex = $monthAverage / $yearAverage;
```

4. **Simulação Monte Carlo** (1000 iterações)
```php
// Gera distribuição de cenários
for ($i = 0; $i < 1000; $i++) {
    $variation = $this->gaussianRandom(0, $stdDev);
    $scenarios[] = $baseProjection * (1 + $variation);
}
// Retorna percentis P10, P50, P90
```

#### Estrutura da Projeção
```php
return [
    'current_month' => [
        'actual' => 2150000,
        'projected' => 2520000,
        'pessimistic' => 2380000,  // P10
        'optimistic' => 2680000,   // P90
        'confidence' => 0.85,
        'days_remaining' => 2,
        'daily_target' => 175000,
        'vs_last_month' => -8.3,
        'vs_last_year' => 12.5
    ],
    'next_month' => [
        'projection' => 2350000,
        'seasonal_factor' => 0.94,
        'growth_factor' => 0.021,
        'range' => ['min' => 2000000, 'max' => 2700000]
    ],
    'chart_data' => [
        'dates' => [...],
        'actual' => [...],
        'projected' => [...]
    ]
];
```

---

### 3.4 Análise RFM - 11 Segmentos

**Arquivo:** `Model/Rfm/Calculator.php` (423 linhas)

#### Segmentos Implementados

| Segmento | R Score | F Score | M Score | Cor | Ação |
|----------|---------|---------|---------|-----|------|
| Champions | 4-5 | 4-5 | 4-5 | Verde | Recompensar |
| Loyal Customers | 3-4 | 3-5 | 3-5 | Azul | Upsell |
| Potential Loyalists | 3-4 | 2-3 | 2-3 | Azul Claro | Engajar |
| Recent Customers | 4-5 | 1 | 1-2 | Ciano | Nutrir |
| Promising | 3-4 | 1 | 1-2 | Teal | Converter |
| Need Attention | 2-3 | 2-3 | 2-3 | Amarelo | Reativar |
| About to Sleep | 2-3 | 1-2 | 1-2 | Laranja | Despertar |
| At Risk | 1-2 | 3-5 | 3-5 | Vermelho | Recuperar Urgente |
| Can't Lose | 1-2 | 4-5 | 4-5 | Vermelho Escuro | Prioridade Máxima |
| Hibernating | 1-2 | 1-2 | 1-2 | Cinza | Win-back |
| Lost | 1 | 1 | 1 | Cinza Escuro | Última chance |

#### Cálculo de Quintis
```php
// Ordena valores e divide em 5 grupos iguais
$quintiles = array_map(function($percentile) use ($sortedValues) {
    $index = (int) floor(count($sortedValues) * $percentile / 100);
    return $sortedValues[$index];
}, [20, 40, 60, 80]);
```

---

### 3.5 Geração de Cupons por Segmento

**Arquivo:** `Model/Coupon/Generator.php` (316 linhas)

#### Descontos por Segmento
```php
$segmentDiscounts = [
    'champions' => 5,
    'loyal_customers' => 10,
    'potential_loyalists' => 15,
    'at_risk' => 20,
    'cant_lose' => 25,
    'hibernating' => 30,
    'lost' => 35
];
```

#### Geração via SalesRule API
```php
// Cria regra de carrinho
$rule = $this->ruleFactory->create();
$rule->setName('ERP Coupon - ' . $segment)
     ->setDiscountAmount($discount)
     ->setSimpleAction('by_percent')
     ->setUsesPerCustomer(1)
     ->setUsesPerCoupon(1)
     ->setFromDate(date('Y-m-d'))
     ->setToDate(date('Y-m-d', strtotime('+30 days')));

// Gera código único
$couponCode = strtoupper(substr($segment, 0, 4)) . '-' . bin2hex(random_bytes(4));
```

---

### 3.6 Alertas por Email

**Arquivo:** `Model/Alert/EmailSender.php` (410 linhas)

#### Tipos de Alerta

1. **At Risk Alert** - Cliente em risco identificado
2. **Re-engagement Email** - Cupom personalizado
3. **Weekly RFM Report** - Relatório semanal de segmentos
4. **Forecast Alert** - Projeção abaixo da meta

---

## 4. API REST Endpoints

**Arquivo:** `etc/webapi.xml` (134 linhas)

### Suggested Cart API
| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET | /V1/erp/suggestions/cart | self | Carrinho sugerido (cliente logado) |
| GET | /V1/erp/suggestions/cart/:customerId | admin | Carrinho de cliente específico |
| GET | /V1/erp/suggestions/reorder | self | Sugestões de recompra |
| GET | /V1/erp/suggestions/crosssell | self | Sugestões cross-sell |

### RFM Analysis API
| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET | /V1/erp/rfm/me | self | RFM do cliente logado |
| GET | /V1/erp/rfm/customer/:customerId | admin | RFM de cliente específico |
| GET | /V1/erp/rfm/segments | admin | Estatísticas por segmento |
| GET | /V1/erp/rfm/at-risk | admin | Lista de clientes em risco |

### Forecast API
| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET | /V1/erp/forecast/current-month | admin | Projeção mês atual |
| GET | /V1/erp/forecast/next-month | admin | Projeção próximo mês |
| GET | /V1/erp/forecast/daily-chart | admin | Dados para gráfico diário |
| GET | /V1/erp/forecast/monthly-comparison | admin | Comparativo mensal |

---

## 5. Permissões ACL

**Arquivo:** `etc/acl.xml` (29 linhas)

```xml
<resource id="GrupoAwamotos_ERPIntegration::erp" title="ERP Integration">
    <resource id="GrupoAwamotos_ERPIntegration::dashboard" title="ERP Dashboard"/>
    <resource id="GrupoAwamotos_ERPIntegration::customers" title="ERP Customers"/>
    <resource id="GrupoAwamotos_ERPIntegration::sync" title="Sync Operations"/>
    <resource id="GrupoAwamotos_ERPIntegration::log" title="Sync Logs"/>
    <resource id="GrupoAwamotos_ERPIntegration::suggestions" title="Product Suggestions"/>
    <resource id="GrupoAwamotos_ERPIntegration::rfm" title="RFM Analysis"/>
    <resource id="GrupoAwamotos_ERPIntegration::forecast" title="Sales Forecast"/>
</resource>
```

---

## 6. Configurações do Módulo

### Admin > Stores > Configuration > GrupoAwamotos > ERP Integration

**Seções disponíveis:**

1. **General Settings**
   - Enable Module
   - Debug Mode
   - Admin Notifications

2. **ERP Connection**
   - Server Host
   - Database Name
   - Username/Password
   - Connection Driver (sqlsrv, dblib, odbc)

3. **Suggested Cart**
   - Enable Feature
   - Min/Max Products
   - Analysis Period (days)
   - Minimum Score Threshold

4. **RFM Analysis**
   - Enable Feature
   - Analysis Period (months)
   - Auto Update Frequency

5. **Forecast**
   - Enable Feature
   - Monte Carlo Iterations
   - Confidence Interval

---

## 7. Frontend Integration

### Widget de Sugestões para Cliente

**Arquivo:** `view/frontend/templates/customer/suggestions.phtml`

Exibe sugestões personalizadas na área do cliente:
- Produtos para recompra
- Cross-sell recommendations
- Botão "Adicionar ao Carrinho"

### Controller para Adicionar Itens

**Arquivo:** `Controller/Cart/AddSuggested.php` (293 linhas)

```php
// POST /erpintegration/cart/addSuggested
{
    "items": [
        {"sku": "ABC123", "qty": 2},
        {"sku": "DEF456", "qty": 1}
    ],
    "add_all": false
}
```

---

## 8. Dependências

### PHP Extensions Requeridas
- pdo_sqlsrv (Windows) ou pdo_dblib (Linux)
- json
- mbstring

### JavaScript Libraries (via CDN)
- ApexCharts 3.x

### Magento Modules
- Magento_Customer
- Magento_Catalog
- Magento_Checkout
- Magento_SalesRule
- Magento_Email

---

## 9. Instalação e Ativação

```bash
# O módulo já está instalado e ativado
php bin/magento module:status GrupoAwamotos_ERPIntegration

# Se necessário recompilar
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## 10. Métricas de Sucesso Esperadas

| Métrica | Baseline | Meta (6 meses) |
|---------|----------|----------------|
| Taxa de Recompra | 35% | 50% |
| Ticket Médio | R$ 2.800 | R$ 3.500 |
| Clientes Ativos | 60% | 75% |
| Retenção "At Risk" | 20% | 45% |
| Acurácia Previsão | N/A | 85% |

---

## 11. Changelog

### v2.0 (30/01/2026)
- Implementação completa do módulo
- Dashboard com ApexCharts
- API REST (12 endpoints)
- Análise RFM com 11 segmentos
- Projeções Monte Carlo
- Geração de cupons por segmento
- Alertas por email
- Controller para adicionar itens sugeridos ao carrinho

### v1.0 (29/01/2026)
- Especificação técnica inicial
- Estrutura proposta do módulo

---

*Documento atualizado em: 30/01/2026*
*Versão: 2.0*
*Módulo: GrupoAwamotos_ERPIntegration*

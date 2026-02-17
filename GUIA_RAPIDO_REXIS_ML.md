# 🚀 Guia Rápido - REXIS ML (Integração Power BI → Magento)

**Tempo estimado:** 15-20 minutos
**Data:** 17/02/2026

---

## ✅ **CHECKLIST DE INSTALAÇÃO**

### **□ Etapa 1: Habilitar Módulo (2 min)**

```bash
cd /home/user/htdocs/srv1113343.hstgr.cloud

# Habilitar módulo
php bin/magento module:enable GrupoAwamotos_RexisML

# Executar setup (cria as 4 tabelas)
php bin/magento setup:upgrade

# Verificar
php bin/magento module:status | grep RexisML
# Deve mostrar: GrupoAwamotos_RexisML
```

**Tabelas criadas:**
- ✅ `rexis_dataset_recomendacao` (recomendações)
- ✅ `rexis_network_rules` (market basket)
- ✅ `rexis_customer_classification` (RFM)
- ✅ `rexis_metricas_conversao` (métricas)

---

### **□ Etapa 2: Verificar Tabelas (1 min)**

```bash
# Conectar ao MySQL
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento

# Verificar tabelas
SHOW TABLES LIKE 'rexis%';

# Deve mostrar:
# +-----------------------------------+
# | Tables_in_magento (rexis%)       |
# +-----------------------------------+
# | rexis_customer_classification    |
# | rexis_dataset_recomendacao       |
# | rexis_metricas_conversao         |
# | rexis_network_rules              |
# +-----------------------------------+

# Ver estrutura de uma tabela
DESCRIBE rexis_dataset_recomendacao;

# Sair
exit;
```

---

### **□ Etapa 3: Instalar Python e Dependências (5 min)**

```bash
# Verificar Python 3
python3 --version
# Deve mostrar: Python 3.x.x

# Se não tiver, instalar:
# sudo apt-get install python3 python3-pip

# Instalar bibliotecas necessárias
pip3 install pymssql pymysql pandas numpy mlxtend

# Verificar instalação
python3 -c "import pymssql, pymysql, pandas, numpy, mlxtend; print('✅ Todas as bibliotecas instaladas!')"
```

---

### **□ Etapa 4: Configurar Credenciais do ERP (2 min)**

```bash
# Editar script Python
nano scripts/rexis_ml_sync.py

# Ou vim
vim scripts/rexis_ml_sync.py
```

**Procurar linhas 18-24 e ajustar:**

```python
ERP_CONFIG = {
    'server': 'SECTRASERVER',
    'database': 'INDUSTRIAL',
    'user': 'SEU_USUARIO_ERP',      # ← PREENCHER AQUI
    'password': 'SUA_SENHA_ERP'      # ← PREENCHER AQUI
}
```

**Salvar:** `Ctrl+O`, `Enter`, `Ctrl+X` (nano) ou `:wq` (vim)

---

### **□ Etapa 5: Dar Permissão de Execução (1 min)**

```bash
chmod +x scripts/rexis_ml_sync.py
ls -lh scripts/rexis_ml_sync.py
# Deve mostrar: -rwxr-xr-x ... rexis_ml_sync.py
```

---

### **□ Etapa 6: Executar Primeira Sincronização (5-10 min)**

#### **Opção A: Via Comando Magento (Recomendado)**

```bash
php bin/magento rexis:sync --full
```

#### **Opção B: Via Python Direto**

```bash
python3 scripts/rexis_ml_sync.py
```

**Saída esperada:**
```
======================================================================
🚀 REXIS ML SYNC - Sincronização de Recomendações
======================================================================

🔌 Conectando ao ERP...
✅ Conectado ao ERP!
🔌 Conectando ao Magento...
✅ Conectado ao Magento!
📊 Extraindo transações dos últimos 12 meses...
✅ 18729 transações extraídas
👥 Extraindo clientes...
✅ 3149 clientes extraídos
📦 Extraindo produtos...
✅ 693 produtos extraídos
📈 Calculando RFM...
✅ RFM calculado para 3149 clientes
🔍 Identificando oportunidades de Churn...
✅ 1250 oportunidades de Churn identificadas
🛒 Calculando Market Basket Analysis...
✅ 150 regras de associação encontradas
🤖 Gerando recomendações...
✅ 8500 recomendações geradas
🗑️ Limpando tabelas antigas...
✅ Tabelas limpas
💾 Inserindo recomendações no Magento...
✅ 8500 recomendações inseridas
💾 Inserindo regras MBA no Magento...
✅ 150 regras MBA inseridas
💾 Inserindo classificações RFM no Magento...
✅ 3149 classificações RFM inseridas

======================================================================
✅ SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO!
======================================================================
📊 Estatísticas:
   - Recomendações: 8500
   - Regras MBA: 150
   - Classificações RFM: 3149
```

---

### **□ Etapa 7: Verificar Dados Importados (2 min)**

```bash
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento << 'EOF'

-- Total de recomendações
SELECT COUNT(*) AS total_recomendacoes
FROM rexis_dataset_recomendacao;

-- Por classificação de produto
SELECT
    classificacao_produto,
    COUNT(*) AS qtd,
    AVG(pred) AS score_medio
FROM rexis_dataset_recomendacao
GROUP BY classificacao_produto
ORDER BY qtd DESC;

-- Top 10 oportunidades de Churn (maior score)
SELECT
    identificador_cliente,
    identificador_produto,
    classificacao_produto,
    pred AS score,
    probabilidade_compra,
    previsao_gasto_round_up
FROM rexis_dataset_recomendacao
WHERE classificacao_produto = 'Oportunidade Churn'
ORDER BY pred DESC
LIMIT 10;

-- Regras de associação (MBA)
SELECT
    antecedent AS produto_A,
    consequent AS produto_B,
    lift,
    confidence
FROM rexis_network_rules
ORDER BY lift DESC
LIMIT 10;

-- Classificação de clientes
SELECT
    classificacao_cliente,
    COUNT(*) AS total_clientes,
    AVG(monetary) AS valor_medio_gasto
FROM rexis_customer_classification
GROUP BY classificacao_cliente
ORDER BY total_clientes DESC;

EOF
```

---

## 🎯 **TESTES FUNCIONAIS**

### **Teste 1: Buscar Recomendações para um Cliente Específico**

```bash
# Escolher um cliente (ex: 699-MARCELO DE NOVAIS)
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento -e "
SELECT
    identificador_produto AS produto_recomendado,
    classificacao_produto AS tipo_oportunidade,
    ROUND(pred, 2) AS score,
    ROUND(probabilidade_compra, 1) AS probabilidade_pct,
    ROUND(previsao_gasto_round_up, 2) AS valor_previsto
FROM rexis_dataset_recomendacao
WHERE identificador_cliente = 'SEU_CNPJ_AQUI'  -- Trocar pelo CNPJ real
  AND pred >= 0.5
ORDER BY pred DESC
LIMIT 20;
"
```

### **Teste 2: Cross-sell para um Produto**

```bash
# Ver produtos complementares (cross-sell)
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento -e "
SELECT
    consequent AS produto_sugerido,
    lift AS forca_associacao,
    ROUND(confidence * 100, 1) AS confianca_pct,
    ROUND(support * 100, 2) AS suporte_pct
FROM rexis_network_rules
WHERE antecedent LIKE '%PEDALEIRA%'  -- Trocar pelo produto que quiser
  AND lift >= 1.5
ORDER BY lift DESC
LIMIT 10;
"
```

### **Teste 3: Dashboard Resumido**

```bash
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento -e "
-- DASHBOARD REXIS ML

SELECT '=== RECOMENDAÇÕES POR TIPO ===' AS '';
SELECT
    classificacao_produto,
    COUNT(*) AS quantidade,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM rexis_dataset_recomendacao), 1), '%') AS percentual
FROM rexis_dataset_recomendacao
GROUP BY classificacao_produto
ORDER BY quantidade DESC;

SELECT '' AS '';
SELECT '=== CLIENTES POR SEGMENTO RFM ===' AS '';
SELECT
    classificacao_cliente,
    COUNT(*) AS clientes,
    CONCAT('R$ ', FORMAT(AVG(monetary), 2)) AS ticket_medio
FROM rexis_customer_classification
GROUP BY classificacao_cliente
ORDER BY clientes DESC;

SELECT '' AS '';
SELECT '=== TOP 5 REGRAS DE CROSS-SELL ===' AS '';
SELECT
    antecedent AS produto_origem,
    consequent AS produto_destino,
    ROUND(lift, 2) AS lift,
    CONCAT(ROUND(confidence * 100, 1), '%') AS confianca
FROM rexis_network_rules
ORDER BY lift DESC
LIMIT 5;
"
```

---

## ⚙️ **CONFIGURAR CRON AUTOMÁTICO**

Para sincronizar automaticamente todos os dias:

```bash
# Editar crontab
crontab -e

# Adicionar linha (executa todo dia às 6h da manhã)
0 6 * * * cd /home/user/htdocs/srv1113343.hstgr.cloud && python3 scripts/rexis_ml_sync.py >> var/log/rexis_sync.log 2>&1

# Salvar e sair

# Verificar
crontab -l | grep rexis
```

---

## 📊 **PRÓXIMOS PASSOS**

Após a sincronização estar funcionando:

### **1. Integrar com SmartSuggestions Existente**

```bash
# O módulo SmartSuggestions pode consumir os dados REXIS:
# - Ler tabela rexis_dataset_recomendacao
# - Exibir no frontend
# - Enviar via WhatsApp
```

### **2. Criar Dashboard no Admin**

- Visualizações gráficas (Chart.js)
- Filtros interativos
- Exportar CSV
- Ações (enviar email, WhatsApp)

### **3. Automações**

```php
// Exemplo: Enviar email para oportunidades de Churn
// app/code/GrupoAwamotos/RexisML/Cron/SendChurnAlerts.php

public function execute()
{
    $churnOpportunities = $this->recomendacaoCollection
        ->addClassificacaoFilter('Oportunidade Churn')
        ->getHighProbability(0.7);

    foreach ($churnOpportunities as $rec) {
        // Enviar email de reativação
        $this->emailSender->sendChurnRecovery($rec);
    }
}
```

### **4. API REST**

```bash
# Endpoint para frontend consumir
GET /rest/V1/rexis/recommendations/:customerId

# Resposta JSON:
{
    "recommendations": [
        {
            "sku": "PEDALEIRA-TITAN-150",
            "type": "Oportunidade Churn",
            "score": 0.85,
            "predicted_value": 150.00
        },
        ...
    ]
}
```

---

## 🐛 **TROUBLESHOOTING**

### **Erro: "Table 'rexis_dataset_recomendacao' doesn't exist"**

```bash
php bin/magento setup:upgrade --keep-generated
```

### **Erro: "ModuleNotFoundError: No module named 'pymssql'"**

```bash
pip3 install pymssql pymysql pandas numpy mlxtend
```

### **Erro: "Connection refused" (ERP)**

- Verificar credenciais em `scripts/rexis_ml_sync.py`
- Testar conexão:
  ```bash
  php bin/magento erp:connection:test
  ```

### **Sincronização lenta**

- Normal para primeira execução (10-15 min)
- Próximas execuções serão incrementais (3-5 min)
- Ajustar `PARAMS['periodo_analise_meses']` para 6 meses se necessário

---

## 📞 **COMANDOS ÚTEIS**

```bash
# Ver status do módulo
php bin/magento module:status GrupoAwamotos_RexisML

# Sincronizar (incremental)
php bin/magento rexis:sync

# Sincronizar (completo - mais lento)
php bin/magento rexis:sync --full

# Ver logs
tail -f var/log/rexis_sync.log
tail -f var/log/system.log | grep -i rexis

# Limpar cache
php bin/magento cache:clean

# Contar registros
mysql -u magento -p'Aw4m0t0s2025Mage' -D magento -e "
SELECT
    'Recomendações' AS tabela,
    COUNT(*) AS registros
FROM rexis_dataset_recomendacao
UNION ALL
SELECT 'Regras MBA', COUNT(*) FROM rexis_network_rules
UNION ALL
SELECT 'Classificações RFM', COUNT(*) FROM rexis_customer_classification
UNION ALL
SELECT 'Métricas', COUNT(*) FROM rexis_metricas_conversao;
"
```

---

## ✅ **VALIDAÇÃO FINAL**

Após completar todas as etapas, verificar:

- [x] Módulo habilitado: `php bin/magento module:status | grep RexisML`
- [x] Tabelas criadas: `SHOW TABLES LIKE 'rexis%'`
- [x] Python instalado: `python3 --version`
- [x] Dependências instaladas: `pip3 list | grep pymssql`
- [x] Credenciais ERP configuradas
- [x] Script executável: `ls -lh scripts/rexis_ml_sync.py`
- [x] Sincronização executada: `php bin/magento rexis:sync`
- [x] Dados importados: `SELECT COUNT(*) FROM rexis_dataset_recomendacao`
- [x] Cron configurado (opcional)

---

**Sistema REXIS ML está pronto para uso! 🚀**

**Próximo passo:** Integrar com frontend e criar dashboard visual.

**Documentação completa:** `app/code/GrupoAwamotos/RexisML/README.md`

**Dúvidas?** Consultar STATUS_SISTEMA_COMPLETO.md ou logs em `var/log/`

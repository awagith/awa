# CLAUDE.md — Instruções para Agentes Claude Code

## REGRA #1: EXECUTE, NÃO INVENTE LIMITAÇÕES

Todos os serviços estão disponíveis e funcionais neste servidor.
**NÃO diga que algo "não está disponível no sandbox".**
Execute diretamente. Você tem permissão total para:

- Rodar queries MySQL
- Executar comandos Magento (bin/magento)
- Executar PHP, Composer, Git, Redis, curl, etc.
- Ler e editar qualquer arquivo do projeto
- Executar setup:upgrade, cache:flush, reindex, di:compile
- Criar scripts temporários em /tmp e executá-los

## Ambiente

- **Stack**: Magento 2.4.7 + Percona Server 8.4 + PHP 8.2 + OpenSearch + Redis
- **Projeto**: Loja B2B — Grupo Awamotos (awamotos.com)
- **Diretório raiz**: `/home/user/htdocs/srv1113343.hstgr.cloud`
- **Web user**: `www-data`

## Serviços Disponíveis — Credenciais e Comandos

### MySQL (Percona 8.4) — RODANDO

```bash
mysql -u magento -p'Aw4m0t0s2025Mage' magento -e "SUA QUERY AQUI"
```

- Host: localhost (socket: `/var/run/mysqld/mysqld.sock`)
- Usuário: `magento` | Banco: `magento`
- Execute queries diretamente. NÃO crie Data Patches para coisas que uma query resolve.
- Para alterações de schema permanentes, use `db_schema.xml` + `setup:upgrade`.

### Redis — RODANDO

```bash
redis-cli ping
redis-cli info memory
```

### OpenSearch — RODANDO (porta 9200)

```bash
curl -s http://localhost:9200/_cluster/health
```

### Magento CLI

```bash
# SEMPRE execute como www-data:
sudo -u www-data php bin/magento cache:flush
sudo -u www-data php bin/magento setup:upgrade
sudo -u www-data php bin/magento setup:di:compile
sudo -u www-data php bin/magento setup:static-content:deploy pt_BR en_US -f
sudo -u www-data php bin/magento indexer:reindex
sudo -u www-data php bin/magento module:status
```

### PHP Scripts

```bash
# Para testar rapidamente, crie scripts em /tmp:
sudo -u www-data php /tmp/meu_teste.php
```

### Composer

```bash
composer show
composer require vendor/pacote
```

### Git

```bash
git status
git add <arquivo>
git commit -m "mensagem"
git push
```

## Módulos Customizados (app/code/GrupoAwamotos/)

| Módulo | Função |
|--------|--------|
| B2B | Aprovação de clientes, listas de preço, CNPJ, cotações, listas de compras, crédito |
| ERPIntegration | Integração com ERP SQL Server (sync produtos, estoque, preços, pedidos, RFM) |
| RexisML | Recomendações e classificação RFM |
| AbandonedCart | Recuperação de carrinho abandonado |
| MaintenanceMode | Modo manutenção customizado |
| SmartSuggestions | Sugestões inteligentes |
| SocialProof | Prova social |
| Vlibras | Acessibilidade (Libras) |
| LayoutFix | Fix de layout admin |

## Regras para Agentes

1. **EXECUTE direto** — MySQL, Redis, Magento CLI, PHP, tudo funciona. Use.
2. **Leia antes de editar** — nunca assuma a estrutura de um arquivo
3. **Padrões Magento** — use di.xml, system.xml, db_schema.xml corretamente
4. **Não crie módulos novos** — estenda os existentes quando possível
5. **Teste após alterações** — rode `setup:upgrade` e `cache:flush`
6. **Permissões de arquivo** — `www-data:www-data` para var/, generated/, pub/
7. **Git** — branch principal: `main`
8. **Não perca tempo** — se precisa de um dado do banco, faça SELECT. Se precisa verificar config, rode `bin/magento config:show`. Ação > especulação.

## Dummy OpenSearch Ativo (28/11/2025)

- Motivo: não é mais necessário manter cluster OpenSearch/Elasticsearch dedicado. Para atender health checks do Magento sem dependência externa, usamos o Dummy OpenSearch local.
- Engine atual: `catalog/search/engine = opensearch` apontando para `http://127.0.0.1:9200`.
- Como iniciar o dummy:

```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
./start_dummy_opensearch.sh
```

- Como aplicar/forçar a configuração de busca:

```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
php scripts/configure_search.php --engine opensearch --host 127.0.0.1 --port 9200 --test 1
```

- Indexadores: manter em `schedule`. Caso necessário reindexar manualmente, evite o `catalogsearch_fulltext` quando o dummy não estiver rodando.

```bash
# Reindex geral (com dummy ativo)
php bin/magento indexer:reindex

# Reindex seletivo (pular busca quando dummy estiver parado)
for idx in $(php bin/magento indexer:info | awk '{print $1}' | grep -v catalogsearch_fulltext); do \
  php bin/magento indexer:reindex "$idx"; \
done
```

- Fallback de busca: o módulo `GrupoAwamotos/Fitment` provê a busca fallback. Recompile a tabela quando houver alterações grandes de catálogo:

```bash
php scripts/fallback_search_rebuild.php --batch 500 --truncate 1
# ou delta
php scripts/fallback_search_delta.php --since "YYYY-MM-DD"
```

- Como voltar para cluster real no futuro:

```bash
php scripts/configure_search.php --engine opensearch --host <HOST_REAL> --port 9200 --user <USER> --pass <PASS>
php bin/magento indexer:reindex catalogsearch_fulltext
```

Observação: documentar no PR/mudança quando o dummy for ligado/desligado para manter rastreabilidade.

# Configuração do Motor de Busca (OpenSearch)

Magento 2.4 exige Elasticsearch ou OpenSearch para indexação e pesquisa de catálogo.
Atualmente o índice `catalogsearch_fulltext` falha com: `No alive nodes found in your cluster`.

## Objetivo
Provisionar um cluster OpenSearch acessível para eliminar erros de indexação e habilitar busca de produtos.

## Parâmetros configurados
```
catalog/search/engine = opensearch
catalog/search/opensearch_server_hostname = 127.0.0.1
catalog/search/opensearch_server_port = 9200
catalog/search/opensearch_index_prefix = awamoto
```

## Provisionamento Local (se Docker disponível)
```bash
docker run -d --name opensearch \
  -e DISABLE_SECURITY_PLUGIN=true \
  -e OPENSEARCH_JAVA_OPTS='-Xms512m -Xmx512m' \
  -p 9200:9200 -p 9600:9600 \
  opensearchproject/opensearch:2.12.0
```
Verificar saúde:
```bash
curl -s http://127.0.0.1:9200/_cluster/health | jq .
```

## Ambiente Sem Docker
- Usar VM ou serviço gerenciado (AWS OpenSearch, Elastic Cloud).
- Garantir liberação de firewall para porta 9200.
- Ajustar hostname em Magento para IP/host público.

## Teste de Conectividade
```bash
curl -s http://HOST:PORT/ -o /dev/null -w 'HTTP:%{http_code}\n'
```
Retorno esperado: `HTTP:200`.

## Reindex Após Disponibilizar Cluster
```bash
php bin/magento indexer:reindex catalogsearch_fulltext
```

## Logs e Diagnóstico
- Ver `var/log/system.log` e `exception.log` para falhas de conexão.
- Usar `curl HOST:PORT/_nodes/http` para validar nós.

## Fallback Temporário
Sem motor de busca funcional:
- Navegação por categorias.
- Formulário de Fitment (Marca/Modelo/Ano) já funcional para filtrar.
- Criar páginas de landing para marcas populares (SEO + UX).

> **Importante:** o script `start_dummy_opensearch.sh` mantém apenas o *ping* saudável para evitar alertas, mas **não suporta indexação**. Mesmo com o dummy ativo o comando `php bin/magento indexer:reindex catalogsearch_fulltext` continuará falhando por falta de um cluster real. Utilize-o somente como medida temporária enquanto o endpoint definitivo é provisionado.

## Próximos Passos
1. Disponibilizar host OpenSearch.
2. Atualizar hostname/port se diferente.
3. Reindexar e validar busca no frontend.
4. Monitorar métricas de desempenho e ajustar JVM flags.

## Notas de Segurança
Em produção manter autenticação e TLS:
- Configurar usuário/senha.
- Usar HTTPS e certificados válidos.
- Limitar IPs de acesso.

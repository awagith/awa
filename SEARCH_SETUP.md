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

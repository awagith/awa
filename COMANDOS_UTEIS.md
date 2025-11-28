# 🔧 Comandos Úteis - Magento 2

## 📋 Índice
- [Cache](#cache)
- [Indexadores](#indexadores)
- [Deploy](#deploy)
- [Módulos](#módulos)
- [Configuração](#configuração)
- [Manutenção](#manutenção)
- [Cron](#cron)
- [Banco de Dados](#banco-de-dados)
- [Performance](#performance)
- [Debug](#debug)

---

## Cache

### Operações Básicas
```bash
# Limpar cache
php bin/magento cache:clean

# Limpar + regenerar
php bin/magento cache:flush

# Status do cache
php bin/magento cache:status

# Habilitar tudo
php bin/magento cache:enable

# Desabilitar tudo
php bin/magento cache:disable

# Habilitar tipo específico
php bin/magento cache:enable config layout block_html

# Desabilitar tipo específico
php bin/magento cache:disable config layout
```

### Tipos de Cache
- `config` - Configuração
- `layout` - Layout
- `block_html` - Blocos HTML
- `collections` - Coleções
- `reflection` - Reflection
- `db_ddl` - DDL do banco
- `compiled_config` - Configuração compilada
- `eav` - Atributos EAV
- `customer_notification` - Notificações
- `config_integration` - Integração
- `config_integration_api` - API de integração
- `full_page` - Full Page Cache
- `config_webservice` - Web Service
- `translate` - Traduções

---

## Indexadores

### Operações
```bash
# Listar indexadores
php bin/magento indexer:info

# Status
php bin/magento indexer:status

# Reindexar tudo
php bin/magento indexer:reindex

# Reindexar específico
php bin/magento indexer:reindex catalog_category_product
php bin/magento indexer:reindex catalog_product_category
php bin/magento indexer:reindex catalog_product_price
php bin/magento indexer:reindex catalogsearch_fulltext

# Resetar indexador
php bin/magento indexer:reset

# Modo de atualização
php bin/magento indexer:set-mode realtime  # Tempo real
php bin/magento indexer:set-mode schedule  # Agendado (produção)

# Modo específico
php bin/magento indexer:set-mode schedule catalog_product_price
```

### Lista de Indexadores
- `design_config_grid` - Design Config Grid
- `customer_grid` - Customer Grid
- `catalog_category_product` - Category Products
- `catalog_product_category` - Product Categories
- `catalogrule_rule` - Catalog Rule Product
- `catalog_product_attribute` - Product EAV
- `inventory` - Inventory
- `catalog_product_price` - Product Price
- `catalogrule_product` - Catalog Product Rule
- `catalogsearch_fulltext` - Catalog Search

---

## Deploy

### Conteúdo Estático
```bash
# Deploy pt_BR
php bin/magento setup:static-content:deploy pt_BR -f

# Deploy multi-idioma
php bin/magento setup:static-content:deploy pt_BR en_US -f

# Deploy com jobs paralelos (4 threads)
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4

# Deploy com área específica
php bin/magento setup:static-content:deploy pt_BR -f --area=adminhtml
php bin/magento setup:static-content:deploy pt_BR -f --area=frontend

# Deploy com tema específico
php bin/magento setup:static-content:deploy pt_BR -f --theme=Magento/luma

# Limpar antes de deploy
rm -rf pub/static/* var/view_preprocessed/*
php bin/magento setup:static-content:deploy pt_BR -f
```

### Compilação
```bash
# Compilar DI
php bin/magento setup:di:compile

# Upgrade (após instalar módulos)
php bin/magento setup:upgrade

# Deploy completo
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4
php bin/magento cache:flush
```

### Modo de Deploy
```bash
# Ver modo atual
php bin/magento deploy:mode:show

# Alterar para developer
php bin/magento deploy:mode:set developer

# Alterar para production
php bin/magento deploy:mode:set production

# Alterar para production mantendo arquivos
php bin/magento deploy:mode:set production --skip-compilation
```

---

## Módulos

### Gerenciamento
```bash
# Listar todos os módulos
php bin/magento module:status

# Listar apenas habilitados
php bin/magento module:status --enabled

# Listar apenas desabilitados
php bin/magento module:status --disabled

# Habilitar módulo
php bin/magento module:enable Vendor_Module

# Habilitar múltiplos
php bin/magento module:enable Vendor_Module1 Vendor_Module2

# Desabilitar módulo
php bin/magento module:disable Vendor_Module

# Desabilitar múltiplos
php bin/magento module:disable Vendor_Module1 Vendor_Module2

# Após habilitar/desabilitar
php bin/magento setup:upgrade
```

---

## Configuração

### Gerenciar Configurações
```bash
# Ver configuração
php bin/magento config:show path/to/config

# Definir configuração
php bin/magento config:set path/to/config "valor"

# Ver todas as configurações
php bin/magento config:show

# Ver configurações específicas
php bin/magento config:show general/locale/code
php bin/magento config:show currency/options/base
php bin/magento config:show web/secure/base_url

# Importar configurações
php bin/magento app:config:import

# Exportar configurações
php bin/magento app:config:dump
```

### Configurações Comuns
```bash
# Locale e Timezone
php bin/magento config:set general/locale/code pt_BR
php bin/magento config:set general/locale/timezone America/Sao_Paulo

# Moeda
php bin/magento config:set currency/options/base BRL
php bin/magento config:set currency/options/default BRL

# URLs
php bin/magento config:set web/unsecure/base_url "http://example.com/"
php bin/magento config:set web/secure/base_url "https://example.com/"

# HTTPS
php bin/magento config:set web/secure/use_in_frontend 1
php bin/magento config:set web/secure/use_in_adminhtml 1

# SEO
php bin/magento config:set web/seo/use_rewrites 1
```

---

## Manutenção

### Modo de Manutenção
```bash
# Habilitar
php bin/magento maintenance:enable

# Desabilitar
php bin/magento maintenance:disable

# Status
php bin/magento maintenance:status

# Permitir IPs específicos
php bin/magento maintenance:allow-ips 192.168.1.1 192.168.1.2

# Limpar lista de IPs
php bin/magento maintenance:allow-ips none
```

---

## Cron

### Gerenciamento
```bash
# Executar cron
php bin/magento cron:run

# Executar grupo específico
php bin/magento cron:run --group=index
php bin/magento cron:run --group=default

# Instalar cron
php bin/magento cron:install

# Remover cron
php bin/magento cron:remove
```

### Crontab
```bash
# Editar crontab
crontab -e

# Adicionar ao crontab:
* * * * * /usr/bin/php /path/to/magento/bin/magento cron:run 2>&1 | grep -v "Ran jobs by schedule" >> /path/to/magento/var/log/magento.cron.log
* * * * * /usr/bin/php /path/to/magento/update/cron.php >> /path/to/magento/var/log/update.cron.log
* * * * * /usr/bin/php /path/to/magento/bin/magento setup:cron:run >> /path/to/magento/var/log/setup.cron.log
```

---

## Banco de Dados

### Backup
```bash
# Backup completo (código + db + media)
php bin/magento setup:backup --code --db --media

# Apenas banco
php bin/magento setup:backup --db

# Apenas código
php bin/magento setup:backup --code

# Apenas media
php bin/magento setup:backup --media

# Rollback
php bin/magento setup:rollback --code-file="<filename>"
php bin/magento setup:rollback --db-file="<filename>"
php bin/magento setup:rollback --media-file="<filename>"
```

### Status
```bash
# Verificar schema
php bin/magento setup:db:status

# Se desatualizado, executar:
php bin/magento setup:upgrade
```

---

## Performance

### Otimizações
```bash
# Habilitar flat catalog
php bin/magento config:set catalog/frontend/flat_catalog_category 1
php bin/magento config:set catalog/frontend/flat_catalog_product 1

# Merge/Minify JS
php bin/magento config:set dev/js/merge_files 1
php bin/magento config:set dev/js/minify_files 1
php bin/magento config:set dev/js/enable_js_bundling 1

# Merge/Minify CSS
php bin/magento config:set dev/css/merge_css_files 1
php bin/magento config:set dev/css/minify_files 1

# Minify HTML
php bin/magento config:set dev/template/minify_html 1

# Assinatura de arquivos estáticos
php bin/magento config:set dev/static/sign 1

# Email assíncrono
php bin/magento config:set sales_email/general/async_sending 1

# Varnish
php bin/magento config:set system/full_page_cache/caching_application 2

# Gerar VCL do Varnish
php bin/magento varnish:vcl:generate > varnish.vcl
```

---

## Debug

### Logs
```bash
# System log
tail -f var/log/system.log

# Exception log
tail -f var/log/exception.log

# Debug log
tail -f var/log/debug.log

# Payment log
tail -f var/log/payment.log

# Relatórios
ls -lah var/report/
```

### Info
```bash
# Versão do Magento
php bin/magento --version

# Lista de comandos
php bin/magento list

# Ajuda de comando específico
php bin/magento help [command]

# Info de idiomas
php bin/magento info:language:list

# Info de moedas
php bin/magento info:currency:list

# Info de timezones
php bin/magento info:timezone:list

# Info de dependências
php bin/magento info:dependencies:show-modules
```

### Admin
```bash
# Criar admin
php bin/magento admin:user:create

# Desbloquear admin
php bin/magento admin:user:unlock adminusername

# Gerar URI do admin
php bin/magento info:adminuri
```

---

## Permissões

### Comandos
```bash
# Permissões padrão
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} +
find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} +
chown -R :www-data .
chmod u+x bin/magento

# Alternativa simplificada
chmod -R 755 var/ pub/static/ pub/media/ generated/
chown -R www-data:www-data var/ pub/ generated/

# Dar permissão ao bin/magento
chmod +x bin/magento
```

---

## Limpeza

### Arquivos Temporários
```bash
# Limpar tudo
rm -rf var/cache/* var/page_cache/* var/view_preprocessed/* pub/static/*

# Limpar apenas cache
rm -rf var/cache/* var/page_cache/*

# Limpar apenas view
rm -rf var/view_preprocessed/*

# Limpar apenas static
rm -rf pub/static/*

# Limpar generated
rm -rf generated/code/* generated/metadata/*

# Após limpar, executar:
php bin/magento cache:flush
php bin/magento setup:static-content:deploy pt_BR -f
```

---

## Store/Website

### Gerenciamento
```bash
# Listar stores
php bin/magento store:list

# Listar websites
php bin/magento store:website:list
```

---

## Busca (Elasticsearch/OpenSearch)

### Comandos
```bash
# Reindexar busca
php bin/magento indexer:reindex catalogsearch_fulltext

# Ver configuração
php bin/magento config:show catalog/search/engine

# Configurar Elasticsearch
php bin/magento config:set catalog/search/engine elasticsearch7
php bin/magento config:set catalog/search/elasticsearch7_server_hostname localhost
php bin/magento config:set catalog/search/elasticsearch7_server_port 9200
```

---

**Última atualização:** 19/11/2025

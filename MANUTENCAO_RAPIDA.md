# 🔧 Guia de Manutenção Rápida - Tema Ayo

**Projeto:** Magento 2.4.8-p3 + Tema Ayo  
**Cliente:** Grupo Awamotos  
**Status:** ✅ Produção Ativa  
**Última Atualização:** 04/12/2025

---

## 🚀 Comandos Essenciais

### Cache
```bash
# Limpar todos os caches
php bin/magento cache:flush

# Limpar caches específicos
php bin/magento cache:flush config layout block_html

# Status dos caches
php bin/magento cache:status

# Habilitar todos
php bin/magento cache:enable
```

### Deploy
```bash
# Deploy estático (production)
php bin/magento setup:static-content:deploy pt_BR en_US -f --jobs=4

# Compilar DI
php bin/magento setup:di:compile

# Modo atual
php bin/magento deploy:mode:show

# Mudar para production
php bin/magento deploy:mode:set production
```

### Indexers
```bash
# Reindexar tudo
php bin/magento indexer:reindex

# Status
php bin/magento indexer:status

# Reindexar específico
php bin/magento indexer:reindex catalog_product_attribute

# Modo schedule (recomendado)
php bin/magento indexer:set-mode schedule
```

---

## 🎨 Tema e Cores

### Paleta Atual
```
Primary Color:     #b73337
Primary Hover:     #8d2729 / #8e2629
Text Color:        #333333
Link Color:        #b73337
Button BG:         #b73337
Button Text:       #FFFFFF
```

### Arquivos Importantes
```
CSS Customizado:   pub/media/custom-colors-b73337.css
Logo Principal:    pub/media/logo/logo.svg
Sticky Logo:       pub/media/logo/sticky-logo.svg
Favicon:           pub/media/logo/favicon.svg
Ícones Pagamento:  pub/media/payment/*.svg (7 arquivos)
```

### Scripts Prontos
```bash
# Localização
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud/scripts/

# Scripts disponíveis:
- criar_blocos_cms_faltantes.php      # 8 blocos CMS
- atualizar_blocos_footer.php         # Footer estruturado
- criar_paginas_cms.php                # 4 páginas essenciais
- aplicar_paleta_cores.php             # Paleta #b73337
- configurar_produtos_featured.php     # Featured/New products

# Executar qualquer um:
php scripts/NOME_DO_SCRIPT.php
```

---

## 📊 Monitoramento

### Logs
```bash
# Erros em tempo real
tail -f var/log/system.log | grep -i "error\|critical"

# Últimas exceções
tail -50 var/log/exception.log

# Deploy estático
tail -f var/log/static-deploy.log

# Cron
tail -f var/log/magento.cron.log
```

### Verificações Rápidas
```bash
# Homepage OK?
curl -I https://srv1113343.hstgr.cloud/

# Páginas CMS OK?
for p in about-us terms privacy-policy shipping-policy; do
  curl -s -o /dev/null -w "/$p: %{http_code}\n" "https://srv1113343.hstgr.cloud/$p"
done

# Produtos featured no banco
mysql -h 127.0.0.1 -u magento -p'*mdYwrnW9PsI0!5Xt^h?' magento -e \
  "SELECT COUNT(*) FROM catalog_product_entity_int 
   WHERE attribute_id=(SELECT attribute_id FROM eav_attribute 
   WHERE attribute_code='featured' AND entity_type_id=4) AND value=1;"
```

---

## 🆘 Troubleshooting

### Homepage HTTP 500
```bash
# 1. Limpar var/view_preprocessed
rm -rf var/view_preprocessed/* var/cache/*

# 2. Recompilar
php bin/magento setup:di:compile

# 3. Redeploy estático
rm -rf pub/static/frontend/* pub/static/adminhtml/*
php bin/magento setup:static-content:deploy pt_BR en_US -f --jobs=4

# 4. Flush cache
php bin/magento cache:flush
```

### CSS/JS não carregando
```bash
# 1. Modo developer temporário
php bin/magento deploy:mode:set developer

# 2. Limpar static
rm -rf pub/static/* var/view_preprocessed/*

# 3. Voltar para production
php bin/magento deploy:mode:set production
php bin/magento setup:static-content:deploy pt_BR en_US -f --jobs=4
```

### Produtos não aparecem
```bash
# 1. Reindexar
php bin/magento indexer:reindex

# 2. Verificar status
php bin/magento indexer:status

# 3. Limpar cache
php bin/magento cache:flush
```

---

## 🔒 Backup & Restore

### Criar Backup
```bash
# Completo (código + banco + mídia)
php bin/magento setup:backup --code --db --media

# Apenas banco
php bin/magento setup:backup --db

# Listar backups
ls -lh var/backups/
```

### Restore Manual
```bash
# 1. Banco de dados
mysql -h 127.0.0.1 -u magento -p'*mdYwrnW9PsI0!5Xt^h?' magento < backup.sql

# 2. Código (via Git)
git checkout feat/paleta-b73337

# 3. Mídia
rsync -av backup/pub/media/ pub/media/
```

---

## ⚡ Otimizações Opcionais

### Performance
```bash
# Habilitar merge/minify JS
php bin/magento config:set dev/js/merge_files 1
php bin/magento config:set dev/js/minify_files 1

# Habilitar merge/minify CSS (já está)
php bin/magento config:set dev/css/merge_css_files 1
php bin/magento config:set dev/css/minify_files 1

# Flush e redeploy
php bin/magento cache:flush
php bin/magento setup:static-content:deploy pt_BR en_US -f --jobs=4
```

### SEO
```bash
# Gerar sitemap
php bin/magento sitemap:generate

# Via Admin:
# Marketing > SEO & Search > Site Map > Add Sitemap
# - Filename: sitemap.xml
# - Path: /
```

---

## 📞 Contatos e Recursos

### URLs
- **Frontend:** https://srv1113343.hstgr.cloud/
- **Admin:** https://srv1113343.hstgr.cloud/admin
- **Documentação:** `README.md`, `GUIA_RAPIDO.md`, `COMANDOS_UTEIS.md`

### Banco de Dados
```
Host: 127.0.0.1
Database: magento
User: magento
Password: *mdYwrnW9PsI0!5Xt^h?
```

### Módulos Rokanthemes Ativos
```
Rokanthemes_AjaxSuite
Rokanthemes_BestsellerProduct
Rokanthemes_Blog
Rokanthemes_Brand
Rokanthemes_Categorytab
Rokanthemes_CustomMenu
Rokanthemes_Faq
Rokanthemes_Featuredpro          ⭐ Produtos Featured
Rokanthemes_Instagram
Rokanthemes_LayeredAjax
Rokanthemes_MostviewedProduct
Rokanthemes_Newproduct           🆕 Produtos Novos
Rokanthemes_OnePageCheckout
Rokanthemes_Onsaleproduct
Rokanthemes_PriceCountdown       ⏱️  Countdown de Preço
Rokanthemes_ProductTab
Rokanthemes_QuickView
Rokanthemes_RokanBase
Rokanthemes_SearchSuiteAutocomplete
Rokanthemes_SlideBanner          🎞️  Slider Homepage
Rokanthemes_SearchbyCat
Rokanthemes_StoreLocator
Rokanthemes_Superdeals
Rokanthemes_Testimonials
Rokanthemes_Themeoption          🎨 Configurações do Tema
Rokanthemes_Toprate
Rokanthemes_VerticalMenu
```

---

## 📋 Checklist Mensal

- [ ] Verificar logs de erro (`var/log/exception.log`)
- [ ] Reindexar todos os indexers
- [ ] Verificar espaço em disco (`df -h`)
- [ ] Backup completo (código + banco + mídia)
- [ ] Atualizar sitemap XML
- [ ] Verificar PageSpeed (Google PageSpeed Insights)
- [ ] Revisar produtos featured (devem ter ~50 ativos)
- [ ] Testar páginas CMS (about-us, terms, privacy-policy, shipping-policy)
- [ ] Verificar copyright no footer
- [ ] Limpar logs antigos (`find var/log -name "*.log" -mtime +30 -delete`)

---

**Preparado por:** Sistema Automatizado  
**Data:** 04/12/2025  
**Versão:** 1.0

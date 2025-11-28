# 🚀 Guia Rápido - Magento 2 Brasil

## 📦 Instalação Automática

Execute o script de configuração automática:
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
./setup-brasil.sh
```

---

## ⚡ Comandos Mais Usados

### Cache
```bash
# Limpar tudo
php bin/magento cache:flush

# Status do cache
php bin/magento cache:status

# Habilitar todos
php bin/magento cache:enable
```

### Indexadores
```bash
# Reindexar tudo
php bin/magento indexer:reindex

# Status
php bin/magento indexer:status

# Modo agendado (produção)
php bin/magento indexer:set-mode schedule
```

### Deploy
```bash
# Deploy rápido pt_BR
php bin/magento setup:static-content:deploy pt_BR -f

# Deploy com 4 jobs (mais rápido)
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4

# Upgrade após instalação de módulos
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

### Manutenção
```bash
# Habilitar modo manutenção
php bin/magento maintenance:enable

# Desabilitar
php bin/magento maintenance:disable

# IPs permitidos durante manutenção
php bin/magento maintenance:allow-ips 192.168.1.1
```

### Modo de Deploy
```bash
# Alterar para produção
php bin/magento deploy:mode:set production

# Verificar modo atual
php bin/magento deploy:mode:show

# Developer mode
php bin/magento deploy:mode:set developer
```

---

## 🇧🇷 Configurações do Brasil

### Rápida Reconfiguração
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# Locale
php bin/magento config:set general/locale/code pt_BR
php bin/magento config:set general/locale/timezone America/Sao_Paulo

# Moeda
php bin/magento config:set currency/options/base BRL
php bin/magento config:set currency/options/default BRL
php bin/magento config:set currency/options/allow "BRL,USD"

# País
php bin/magento config:set general/country/default BR
```

---

## 🔧 Troubleshooting

### Erros Comuns

**1. Permissões**
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
chmod -R 755 var/ pub/static/ pub/media/ generated/
chown -R www-data:www-data var/ pub/ generated/
```

**2. Após instalar módulo**
```bash
php bin/magento module:enable Vendor_Module
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR -f
php bin/magento cache:flush
php bin/magento indexer:reindex
```

**3. Erro 404 em Admin**
```bash
php bin/magento setup:upgrade
php bin/magento cache:flush
```

**4. CSS/JS não carrega**
```bash
rm -rf pub/static/* var/view_preprocessed/*
php bin/magento setup:static-content:deploy pt_BR -f
php bin/magento cache:flush
```

**5. Página em branco**
```bash
# Verificar logs
tail -f var/log/system.log
tail -f var/log/exception.log

# Verificar permissões
chmod -R 755 var/ pub/ generated/
```

---

## 📦 Instalar Módulos da Biblioteca

### Passo a passo
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# 1. Descompactar módulo
unzip biblioteca/modulos/nome-do-modulo.zip -d app/code/

# 2. Habilitar
php bin/magento module:enable Vendor_Module

# 3. Instalar
php bin/magento setup:upgrade
php bin/magento setup:di:compile

# 4. Deploy
php bin/magento setup:static-content:deploy pt_BR -f

# 5. Reindexar e limpar cache
php bin/magento indexer:reindex
php bin/magento cache:flush
```

### Módulos Disponíveis
- Amasty_MassProductActions
- Amasty_AdvancedPermissions
- Amasty_CronScheduler
- Amasty_Promo (Special Promotions Pro)
- Amasty_Acart (Abandoned Cart)
- Amasty_ShippingTableRates
- Webkul_Marketplace
- Mgs_Portfolio
- Mgs_StoreLocator

---

## 🔌 Integrações Recomendadas

### MercadoPago
```bash
composer require mercadopago/magento2-plugin
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Correios
```bash
composer require pedrosousa/magento2-correios
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### PagSeguro
```bash
composer require pagseguro/pagseguro-magento2
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## 🎯 Otimizações de Produção

### Checklist Final
```bash
# 1. Modo produção
php bin/magento deploy:mode:set production

# 2. Compilar
php bin/magento setup:di:compile

# 3. Deploy estático
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4

# 4. Permissões
chmod -R 755 var/ pub/static/ pub/media/ generated/
chown -R www-data:www-data var/ pub/ generated/

# 5. Reindexar
php bin/magento indexer:reindex

# 6. Cache
php bin/magento cache:flush
php bin/magento cache:enable
```

---

## 📊 Monitoramento

### Verificar Status
```bash
# Versão do Magento
php bin/magento --version

# Módulos habilitados
php bin/magento module:status

# Status do cache
php bin/magento cache:status

# Status dos indexadores
php bin/magento indexer:status

# Configuração de locale
php bin/magento config:show general/locale/code

# Configuração de moeda
php bin/magento config:show currency/options/base
```

### Logs
```bash
# Logs do sistema
tail -f var/log/system.log

# Logs de exceções
tail -f var/log/exception.log

# Logs de debug
tail -f var/log/debug.log

# Logs de cron
tail -f var/log/magento.cron.log
```

---

## 🔐 Segurança

### Habilitar 2FA
```bash
php bin/magento module:enable Magento_TwoFactorAuth
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Forçar HTTPS
```bash
php bin/magento config:set web/secure/use_in_frontend 1
php bin/magento config:set web/secure/use_in_adminhtml 1
```

### Alterar URL do Admin
```bash
# Editar app/etc/env.php
# Alterar 'frontName' => 'admin' para outro nome
# Exemplo: 'frontName' => 'paineladmin'

php bin/magento cache:flush
```

---

## 💾 Backup

### Backup Completo
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# Via CLI Magento
php bin/magento setup:backup --code --db --media

# Manual
tar -czf backup-$(date +%Y%m%d).tar.gz \
  --exclude='var' \
  --exclude='pub/media' \
  --exclude='pub/static' \
  /home/jessessh/htdocs/srv1113343.hstgr.cloud
```

### Backup de Banco
```bash
# Via mysqldump
mysqldump -u magento -p magento > backup-db-$(date +%Y%m%d).sql

# Via Magento CLI
php bin/magento setup:backup --db
```

---

## 🆘 Suporte

### Arquivos Importantes
- **Documentação Completa:** `IMPLEMENTACAO_BRASIL.md`
- **Script de Setup:** `setup-brasil.sh`
- **Este Guia:** `GUIA_RAPIDO.md`

### URLs
- **Frontend:** https://srv1113343.hstgr.cloud
- **Admin:** https://srv1113343.hstgr.cloud/admin
- **Documentação:** [Magento DevDocs](https://devdocs.magento.com/)

### Comandos de Diagnóstico
```bash
# Verificar PHP
php -v
php -m | grep -i required_extension

# Verificar Composer
composer --version

# Verificar MySQL
mysql --version

# Verificar espaço em disco
df -h

# Verificar memória
free -h

# Processos do PHP
ps aux | grep php
```

---

## 📚 Recursos

- 📖 **Documentação Completa:** [IMPLEMENTACAO_BRASIL.md](IMPLEMENTACAO_BRASIL.md)
- 🚀 **Script Automático:** `./setup-brasil.sh`
- 🌐 **Magento DevDocs:** https://devdocs.magento.com/
- 🇧🇷 **Magento Brasil:** https://www.magentobrasil.com/

---

**Última atualização:** 19/11/2025

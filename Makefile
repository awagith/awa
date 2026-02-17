SHELL := /usr/bin/env bash
.DEFAULT_GOAL := help

LOCALE ?= pt_BR
JOBS ?= 4
BASE_URL ?= https://awamotos.com/

.PHONY: help
help: ## Mostra esta ajuda
	@echo "Targets disponíveis:" 
	@grep -E '^[a-zA-Z0-9_.-]+:.*## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "  %-28s %s\n", $$1, $$2}'
	@echo ""
	@echo "Variáveis:" 
	@echo "  LOCALE=$(LOCALE)" 
	@echo "  JOBS=$(JOBS)" 
	@echo "  BASE_URL=$(BASE_URL)" 

.PHONY: smoke-frontend
smoke-frontend: ## Smoke test HTTP do frontend (BASE_URL)
	@./scripts/smoke_frontend.sh --url "$(BASE_URL)"

.PHONY: smoke-frontend-insecure
smoke-frontend-insecure: ## Smoke test HTTP do frontend (TLS insecure)
	@./scripts/smoke_frontend.sh --url "$(BASE_URL)" --insecure

.PHONY: doctor
doctor: ## Diagnóstico rápido (não destrutivo)
	@./scripts/magento_doctor.sh

.PHONY: store-setup
store-setup: ## Reaplica seed idempotente (CMS/tema/categorias) do GrupoAwamotos
	@php bin/magento grupoawamotos:store:setup

.PHONY: predeploy
predeploy: ## Checagem pré-deploy (não destrutiva)
	@./scripts/predeploy_check.sh

.PHONY: postdeploy
postdeploy: ## Verificação pós-deploy (não destrutiva)
	@./scripts/postdeploy_verify.sh

.PHONY: fix-permissions
fix-permissions: ## Corrige permissões de var/, pub/, generated/
	@./scripts/fix_permissions.sh

.PHONY: deploy
deploy: ## Deploy (sequência sagrada) com LOCALE/JOBS
	@./scripts/deploy_sagrado.sh --locale "$(LOCALE)" --jobs "$(JOBS)"

.PHONY: deploy-prod
deploy-prod: ## Deploy em production (sem manutenção)
	@./scripts/deploy_sagrado.sh --mode production --locale "$(LOCALE)" --jobs "$(JOBS)"

.PHONY: deploy-prod-maint
deploy-prod-maint: ## Deploy em production com maintenance
	@./scripts/deploy_sagrado.sh --mode production --maintenance --locale "$(LOCALE)" --jobs "$(JOBS)"

.PHONY: deploy-prod-maint-clean
deploy-prod-maint-clean: ## Deploy em production + maintenance + limpeza de estáticos (cuidado)
	@./scripts/deploy_sagrado.sh --mode production --maintenance --clean-static --locale "$(LOCALE)" --jobs "$(JOBS)"

.PHONY: cache-status
cache-status: ## Status do cache
	@php bin/magento cache:status

.PHONY: cache-flush
cache-flush: ## Flush do cache
	@php bin/magento cache:flush

.PHONY: frontend
frontend: ## Deploy frontend RÁPIDO (detecta tema ativo automaticamente)
	@./scripts/deploy_frontend.sh

.PHONY: frontend-full
frontend-full: ## Deploy frontend COMPLETO (limpa tudo, DI, todos temas)
	@./scripts/deploy_frontend.sh --full

.PHONY: indexer-status
indexer-status: ## Status dos indexadores
	@php bin/magento indexer:status

.PHONY: reindex
reindex: ## Reindexar tudo
	@php bin/magento indexer:reindex

.PHONY: mode-show
mode-show: ## Mostrar modo (developer/production)
	@php bin/magento deploy:mode:show

.PHONY: mode-dev
mode-dev: ## Alterar para developer
	@php bin/magento deploy:mode:set developer

.PHONY: mode-prod
mode-prod: ## Alterar para production
	@php bin/magento deploy:mode:set production

.PHONY: logs
logs: ## Tail de logs (system + exception)
	@./scripts/logs_tail.sh

.PHONY: security-audit
security-audit: ## Auditoria rápida de segurança (não destrutiva)
	@./scripts/security_audit.sh

.PHONY: hardening-report
hardening-report: ## Relatório de hardening (não destrutivo)
	@./scripts/hardening_report.sh

.PHONY: hardening
hardening: ## Executa security-audit + hardening-report
	@$(MAKE) security-audit
	@$(MAKE) hardening-report

.PHONY: varnish-vcl
varnish-vcl: ## Gerar VCL do Varnish em var/varnish/magento.vcl
	@./scripts/generate_varnish_vcl.sh

.PHONY: enable-2fa-dry
enable-2fa-dry: ## Mostra o que faria para habilitar 2FA
	@./scripts/enable_2fa.sh

.PHONY: enable-2fa
enable-2fa: ## Habilita 2FA (ATENÇÃO) - requer acesso admin
	@./scripts/enable_2fa.sh --apply

.PHONY: cron-check
cron-check: ## Verifica se cron está “vivo” (baseado no magento.cron.log)
	@./scripts/cron_health.sh

.PHONY: permissions-dry
permissions-dry: ## Mostra as ações de permissões (dry-run)
	@./scripts/permissions_reset.sh

.PHONY: permissions
permissions: ## Aplica reset de permissões (requer privilégios para chown)
	@./scripts/permissions_reset.sh --apply

.PHONY: permissions-harden
permissions-harden: ## Remove permissões world-writable (o+w) (dry-run)
	@./scripts/permissions_harden.sh

.PHONY: permissions-harden-apply
permissions-harden-apply: ## Remove permissões world-writable (o+w) (aplica)
	@./scripts/permissions_harden.sh --apply

.PHONY: permissions-lockdown
permissions-lockdown: ## Lockdown de permissões (dry-run)
	@./scripts/permissions_lockdown.sh

.PHONY: permissions-lockdown-apply
permissions-lockdown-apply: ## Lockdown de permissões (aplica)
	@./scripts/permissions_lockdown.sh --apply

# ====== ERP Integration ======

.PHONY: erp-sync-products
erp-sync-products: ## Sincroniza produtos do ERP (texto/preço/status)
	@php bin/magento erp:sync:products

.PHONY: erp-sync-images
erp-sync-images: ## Sincroniza imagens de produtos do ERP
	@php bin/magento erp:sync:images

.PHONY: erp-sync-images-force
erp-sync-images-force: ## Sincroniza imagens do ERP (força mesmo se desabilitado)
	@php bin/magento erp:sync:images --force

.PHONY: erp-sync-all
erp-sync-all: ## Sincroniza tudo: produtos + imagens + estoque + preços
	@echo "=== Sincronizando Produtos ==="
	@php bin/magento erp:sync:products
	@echo ""
	@echo "=== Sincronizando Imagens ==="
	@php bin/magento erp:sync:images
	@echo ""
	@echo "=== Sincronizando Estoque ==="
	@php bin/magento erp:sync:stock
	@echo ""
	@echo "=== Sincronizando Preços ==="
	@php bin/magento erp:sync:prices
	@echo ""
	@echo "=== Flush de cache ==="
	@php bin/magento cache:flush
	@echo "✅ Sync completo!"

.PHONY: erp-fix-images
erp-fix-images: ## Diagnóstico + correção + sync de imagens ERP (all-in-one)
	@php scripts/fix_and_sync_erp_images.php

.PHONY: erp-diagnose-images
erp-diagnose-images: ## Diagnóstico de imagens ERP (somente leitura)
	@php scripts/diagnostico_imagens_erp.php

.PHONY: erp-status
erp-status: ## Status da integração ERP (conexão, tabelas, contagens)
	@php bin/magento erp:status

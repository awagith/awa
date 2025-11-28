#!/bin/bash
################################################################################
# Script de Configuração Automática - Magento 2 para Brasil
# Versão: 1.0
# Data: 19/11/2025
# 
# Este script aplica todas as configurações brasileiras automaticamente
################################################################################

set -e  # Parar em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Diretório base do Magento
MAGENTO_DIR="/home/jessessh/htdocs/srv1113343.hstgr.cloud"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  Configuração Automática Magento 2 - Mercado Brasileiro       ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

cd "$MAGENTO_DIR"

# Verificar se está no diretório correto
if [ ! -f "bin/magento" ]; then
    echo -e "${RED}❌ Erro: Diretório do Magento não encontrado!${NC}"
    exit 1
fi

echo -e "${YELLOW}📍 Diretório: $MAGENTO_DIR${NC}"
echo ""

################################################################################
# FASE 1: LOCALIZAÇÃO
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 1: Configurando Localização (pt_BR)${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set general/locale/code pt_BR
php bin/magento config:set general/locale/timezone America/Sao_Paulo
php bin/magento config:set general/locale/weight_unit kgs

echo -e "${GREEN}✅ Locale configurado: pt_BR${NC}"
echo -e "${GREEN}✅ Timezone: America/Sao_Paulo${NC}"
echo -e "${GREEN}✅ Unidade de peso: Kg${NC}"
echo ""

################################################################################
# FASE 2: MOEDA E PAÍS
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 2: Configurando Moeda (BRL) e País (BR)${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set currency/options/base BRL
php bin/magento config:set currency/options/allow "BRL,USD"
php bin/magento config:set currency/options/default BRL
php bin/magento config:set general/country/default BR
php bin/magento config:set general/country/allow BR

echo -e "${GREEN}✅ Moeda base: BRL (Real Brasileiro)${NC}"
echo -e "${GREEN}✅ País padrão: Brasil${NC}"
echo ""

################################################################################
# FASE 3: MÉTODOS DE PAGAMENTO
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 3: Habilitando Métodos de Pagamento Brasileiros${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set payment/banktransfer/active 1
php bin/magento config:set payment/banktransfer/title "Transferência Bancária / PIX"
php bin/magento config:set payment/checkmo/active 1
php bin/magento config:set payment/checkmo/title "Boleto Bancário"

echo -e "${GREEN}✅ Transferência Bancária / PIX habilitado${NC}"
echo -e "${GREEN}✅ Boleto Bancário habilitado${NC}"
echo ""

################################################################################
# FASE 4: MÉTODOS DE ENVIO
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 4: Configurando Métodos de Envio${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set carriers/flatrate/active 1
php bin/magento config:set carriers/flatrate/title "Correios"
php bin/magento config:set carriers/flatrate/name "Sedex/PAC"
php bin/magento config:set carriers/tablerate/active 1
php bin/magento config:set carriers/tablerate/title "Transportadora"
php bin/magento config:set carriers/freeshipping/active 1
php bin/magento config:set carriers/freeshipping/title "Frete Grátis"

echo -e "${GREEN}✅ Correios (Sedex/PAC) habilitado${NC}"
echo -e "${GREEN}✅ Transportadora (Table Rate) habilitado${NC}"
echo -e "${GREEN}✅ Frete Grátis habilitado${NC}"
echo ""

################################################################################
# FASE 5: OTIMIZAÇÕES DE PERFORMANCE
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 5: Aplicando Otimizações de Performance${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# JavaScript
php bin/magento config:set dev/js/merge_files 1
php bin/magento config:set dev/js/enable_js_bundling 1
php bin/magento config:set dev/js/minify_files 1
php bin/magento config:set dev/js/move_script_to_bottom 1

# CSS
php bin/magento config:set dev/css/merge_css_files 1
php bin/magento config:set dev/css/minify_files 1

# HTML
php bin/magento config:set dev/template/minify_html 1
php bin/magento config:set dev/static/sign 1

# Flat Catalog
php bin/magento config:set catalog/frontend/flat_catalog_category 1
php bin/magento config:set catalog/frontend/flat_catalog_product 1

# Email Assíncrono
php bin/magento config:set sales_email/general/async_sending 1

# Full Page Cache
php bin/magento config:set system/full_page_cache/caching_application 2

echo -e "${GREEN}✅ JS/CSS: Merge, Bundle e Minify habilitados${NC}"
echo -e "${GREEN}✅ HTML minificado${NC}"
echo -e "${GREEN}✅ Flat Catalog habilitado${NC}"
echo -e "${GREEN}✅ Email assíncrono habilitado${NC}"
echo -e "${GREEN}✅ Varnish configurado${NC}"
echo ""

################################################################################
# FASE 6: SEO
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 6: Configurando SEO${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set design/search_engine_robots/default_robots "INDEX,FOLLOW"
php bin/magento config:set web/seo/use_rewrites 1
php bin/magento config:set catalog/seo/product_use_categories 1

echo -e "${GREEN}✅ URLs amigáveis habilitadas${NC}"
echo -e "${GREEN}✅ Robots: INDEX, FOLLOW${NC}"
echo -e "${GREEN}✅ Produtos com categorias na URL${NC}"
echo ""

################################################################################
# FASE 7: SEGURANÇA
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 7: Configurando Segurança${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set admin/security/use_form_key 1
php bin/magento config:set admin/security/password_is_forced 1
php bin/magento config:set admin/security/password_lifetime 90
php bin/magento config:set web/cookie/cookie_lifetime 86400
php bin/magento config:set admin/security/session_lifetime 86400

echo -e "${GREEN}✅ Form Keys obrigatórios${NC}"
echo -e "${GREEN}✅ Senhas fortes obrigatórias${NC}"
echo -e "${GREEN}✅ Expiração de senha: 90 dias${NC}"
echo -e "${GREEN}✅ Sessões: 24 horas${NC}"
echo ""

################################################################################
# FASE 8: CONFIGURAÇÕES DE CLIENTE
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 8: Configurações de Cliente${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento config:set customer/account_share/scope 0
php bin/magento config:set customer/address/telephone_show req
php bin/magento config:set customer/address/taxvat_show req

echo -e "${GREEN}✅ Telefone obrigatório${NC}"
echo -e "${GREEN}✅ CPF/CNPJ obrigatório${NC}"
echo ""

################################################################################
# FASE 9: INDEXADORES
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 9: Configurando Indexadores${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento indexer:set-mode schedule

echo -e "${GREEN}✅ Indexadores em modo agendado (schedule)${NC}"
echo ""

################################################################################
# FASE 10: LIMPAR CACHE
################################################################################
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}FASE 10: Limpando Cache${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

php bin/magento cache:flush

echo -e "${GREEN}✅ Cache limpo${NC}"
echo ""

################################################################################
# RESUMO FINAL
################################################################################
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                  CONFIGURAÇÃO CONCLUÍDA! ✅                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📊 RESUMO DAS CONFIGURAÇÕES:${NC}"
echo ""
echo -e "  ${GREEN}✅${NC} Idioma: Português do Brasil (pt_BR)"
echo -e "  ${GREEN}✅${NC} Timezone: America/Sao_Paulo"
echo -e "  ${GREEN}✅${NC} Moeda: Real Brasileiro (BRL)"
echo -e "  ${GREEN}✅${NC} País: Brasil"
echo -e "  ${GREEN}✅${NC} Pagamentos: Transferência/PIX, Boleto"
echo -e "  ${GREEN}✅${NC} Envios: Correios, Transportadora, Frete Grátis"
echo -e "  ${GREEN}✅${NC} Performance: JS/CSS otimizados, Cache habilitado"
echo -e "  ${GREEN}✅${NC} SEO: URLs amigáveis, Robots configurados"
echo -e "  ${GREEN}✅${NC} Segurança: Senhas fortes, Form Keys"
echo ""
echo -e "${YELLOW}📝 PRÓXIMOS PASSOS RECOMENDADOS:${NC}"
echo ""
echo -e "  1️⃣  Instalar pacote de tradução pt_BR:"
echo -e "     ${YELLOW}composer require mageplaza/magento-2-portuguese-brazil-language-pack:dev-master${NC}"
echo ""
echo -e "  2️⃣  Executar upgrade e compilação:"
echo -e "     ${YELLOW}php bin/magento setup:upgrade${NC}"
echo -e "     ${YELLOW}php bin/magento setup:di:compile${NC}"
echo ""
echo -e "  3️⃣  Deploy de conteúdo estático:"
echo -e "     ${YELLOW}php bin/magento setup:static-content:deploy pt_BR -f${NC}"
echo ""
echo -e "  4️⃣  Reindexar:"
echo -e "     ${YELLOW}php bin/magento indexer:reindex${NC}"
echo ""
echo -e "  5️⃣  Integrar gateway de pagamento brasileiro (MercadoPago/PagSeguro)"
echo ""
echo -e "  6️⃣  Configurar integração real dos Correios"
echo ""
echo -e "  7️⃣  Configurar Redis e Varnish para produção"
echo ""
echo -e "${GREEN}📚 Documentação completa em: IMPLEMENTACAO_BRASIL.md${NC}"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

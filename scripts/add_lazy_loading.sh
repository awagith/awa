#!/bin/bash
##############################################################################
# Script: add_lazy_loading.sh
# Descrição: Adiciona loading="lazy" em imagens dos templates do tema Ayo
# Autor: Grupo Awamotos
# Data: 05/12/2025
##############################################################################

THEME_PATH="/home/jessessh/htdocs/srv1113343.hstgr.cloud/app/design/frontend/Rokanthemes/ayo"
BACKUP_PATH="/home/jessessh/htdocs/srv1113343.hstgr.cloud/var/backups/templates_$(date +%Y%m%d_%H%M%S)"

echo "🔍 Iniciando adição de lazy loading..."
echo ""

# Criar backup
echo "📦 Criando backup em $BACKUP_PATH"
mkdir -p "$BACKUP_PATH"
cp -r "$THEME_PATH" "$BACKUP_PATH/"

# Encontrar templates com imagens sem lazy loading
echo ""
echo "🔎 Buscando templates com <img> sem loading='lazy'..."
echo ""

# Adicionar lazy loading em imagens de produto
find "$THEME_PATH" -name "*.phtml" -type f | while read file; do
    if grep -q '<img' "$file" && ! grep -q 'loading="lazy"' "$file"; then
        echo "📝 Processando: $file"
        
        # Adicionar loading="lazy" em tags <img> que não tenham
        sed -i 's/<img \([^>]*\)>/<img loading="lazy" \1>/g' "$file"
        sed -i 's/loading="lazy" loading="lazy"/loading="lazy"/g' "$file"
    fi
done

echo ""
echo "✅ Lazy loading adicionado com sucesso!"
echo ""
echo "📊 Estatísticas:"
grep -r 'loading="lazy"' "$THEME_PATH" --include="*.phtml" | wc -l | xargs echo "   Total de imagens com lazy loading:"
echo ""
echo "♻️  Próximos passos:"
echo "   1. php bin/magento cache:flush"
echo "   2. php bin/magento setup:static-content:deploy pt_BR -f"
echo ""

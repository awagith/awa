#!/bin/bash
# Script para detectar requisições 404 com padrões de hash (64 caracteres hex)
# Uso: ./check_hash_404_errors.sh [caminho_do_access_log]

LOG_FILE="${1:-/var/log/nginx/access.log}"
TEMP_FILE=$(mktemp)

echo "==========================================="
echo "Análise de Erros 404 com Hashes"
echo "Arquivo de log: $LOG_FILE"
echo "==========================================="
echo ""

# Verificar se arquivo existe
if [ ! -f "$LOG_FILE" ]; then
    echo "ERRO: Arquivo de log não encontrado: $LOG_FILE"
    echo ""
    echo "Tente especificar o caminho correto:"
    echo "  ./check_hash_404_errors.sh /caminho/para/access.log"
    echo ""
    echo "Caminhos comuns:"
    echo "  - /var/log/nginx/access.log"
    echo "  - /var/log/apache2/access.log"
    echo "  - /var/log/httpd/access_log"
    exit 1
fi

# Procurar por 404 com hash de 64 caracteres hex no caminho
echo "🔍 Procurando requisições 404 com hash de 64 caracteres..."
grep ' 404 ' "$LOG_FILE" | grep -E '/[0-9a-f]{64}' > "$TEMP_FILE"

TOTAL_404_HASH=$(wc -l < "$TEMP_FILE")

if [ "$TOTAL_404_HASH" -eq 0 ]; then
    echo "✅ Nenhum erro 404 com hash de 64 caracteres encontrado!"
    rm -f "$TEMP_FILE"
    exit 0
fi

echo "⚠️  Total de erros 404 com hash: $TOTAL_404_HASH"
echo ""

# Top 10 hashes mais requisitados
echo "📊 Top 10 hashes com mais 404:"
echo "-------------------------------------------"
grep -oE '/[0-9a-f]{64}[^" ]*' "$TEMP_FILE" | sort | uniq -c | sort -rn | head -10
echo ""

# Top User-Agents gerando esses erros
echo "🌐 Top 5 User-Agents gerando esses 404:"
echo "-------------------------------------------"
grep -oP 'Mozilla[^"]*|curl[^"]*|python-requests[^"]*' "$TEMP_FILE" | sort | uniq -c | sort -rn | head -5
echo ""

# IPs mais ativos
echo "📍 Top 5 IPs gerando esses 404:"
echo "-------------------------------------------"
grep -oE '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' "$TEMP_FILE" | sort | uniq -c | sort -rn | head -5
echo ""

# Últimas 5 requisições
echo "🕐 Últimas 5 requisições com hash 404:"
echo "-------------------------------------------"
tail -5 "$TEMP_FILE" | while IFS= read -r line; do
    echo "$line" | grep -oE '[0-9]{2}/[A-Za-z]{3}/[0-9]{4}:[0-9:]+|/[0-9a-f]{64}[^ ]*' | xargs echo
done
echo ""

# Procurar especificamente pelo hash mencionado
SPECIFIC_HASH="5ca312d115530745fa8fbfb0d8a48561d951fb7b82b60fa2ebb8a06566ca8f29"
echo "🎯 Procurando hash específico: $SPECIFIC_HASH"
SPECIFIC_COUNT=$(grep -c "$SPECIFIC_HASH" "$LOG_FILE" || echo "0")
if [ "$SPECIFIC_COUNT" -gt 0 ]; then
    echo "   Encontrado $SPECIFIC_COUNT vezes no log"
    echo ""
    echo "   Exemplos:"
    grep "$SPECIFIC_HASH" "$LOG_FILE" | head -3 | while IFS= read -r line; do
        echo "   - $(echo "$line" | grep -oE '[0-9]{2}/[A-Za-z]{3}/[0-9]{4}:[0-9:]+ [+-][0-9]+|/[0-9a-f]{64}[^ ]*|" [0-9]{3} ' | xargs)"
    done
else
    echo "   ❌ Hash específico NÃO encontrado no log"
fi
echo ""

# Recomendações
echo "💡 RECOMENDAÇÕES:"
echo "-------------------------------------------"
echo "1. Verifique se Service Workers estão ativos no navegador"
echo "   - Chrome: DevTools > Application > Service Workers"
echo "   - Firefox: about:debugging > Service Workers"
echo ""
echo "2. Teste em modo anônimo sem extensões"
echo ""
echo "3. Execute limpeza de estáticos se necessário:"
echo "   cd /home/jessessh/htdocs/srv1113343.hstgr.cloud"
echo "   bin/magento cache:flush"
echo "   rm -rf pub/static/frontend pub/static/adminhtml var/view_preprocessed/*"
echo "   bin/magento setup:static-content:deploy -f pt_BR en_US"
echo ""
echo "4. Use o snippet JS para limpar Service Workers (veja public/clear-sw.html)"
echo ""

# Cleanup
rm -f "$TEMP_FILE"

echo "==========================================="
echo "Análise concluída!"
echo "==========================================="

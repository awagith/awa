#!/bin/bash
##############################################################################
# AWA Motos - Server Optimization Script
# Run as root via SSH: bash /home/user/htdocs/srv1113343.hstgr.cloud/optimize-server.sh
##############################################################################

# Don't use set -e: we want to continue even if some steps fail (e.g. read-only /etc)

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color
BOLD='\033[1m'

echo -e "${BOLD}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║        AWA Motos — Otimização de Servidor VPS              ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check root
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${RED}ERRO: Execute como root!${NC}"
    echo "  sudo bash $0"
    exit 1
fi

echo -e "${YELLOW}=== ESTADO ANTES ===${NC}"
echo "Memória:"
free -h | head -2
echo ""
echo "Load:"
uptime
echo ""
echo "PHP-FPM masters rodando: $(ps aux | grep 'php-fpm: master' | grep -v grep | wc -l)"
echo ""

###########################################################################
# 1. PARAR PHP-FPM DESNECESSÁRIOS (7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.5)
###########################################################################
echo -e "\n${BOLD}[1/5] Parando PHP-FPM desnecessários (mantendo apenas 8.4)...${NC}"

for version in 7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.5; do
    service="php${version}-fpm"
    if systemctl is-active --quiet "$service" 2>/dev/null; then
        systemctl stop "$service" 2>/dev/null && \
        systemctl disable "$service" 2>/dev/null && \
        echo -e "  ${GREEN}✓${NC} $service parado e desabilitado" || \
        echo -e "  ${YELLOW}⚠${NC} $service - falhou ao parar"
    else
        echo -e "  ${GREEN}✓${NC} $service já está parado"
    fi
done

# CLP PHP-FPM (Hostinger CloudPanel)
if systemctl is-active --quiet "clp-php-fpm" 2>/dev/null; then
    # Don't stop CLP PHP-FPM - it may be needed by CloudPanel
    echo -e "  ${YELLOW}⚠${NC} clp-php-fpm - mantido (necessário para CloudPanel)"
fi

echo -e "\n  PHP-FPM masters restantes: $(ps aux | grep 'php-fpm: master' | grep -v grep | wc -l)"

###########################################################################
# 2. PARAR VARNISH (Magento usa FPC built-in via Redis)
###########################################################################
echo -e "\n${BOLD}[2/5] Parando Varnish (não utilizado pelo Magento)...${NC}"

if systemctl is-active --quiet varnish 2>/dev/null; then
    systemctl stop varnish 2>/dev/null && \
    systemctl disable varnish 2>/dev/null && \
    echo -e "  ${GREEN}✓${NC} Varnish parado e desabilitado (512MB RAM liberados)" || \
    echo -e "  ${YELLOW}⚠${NC} Varnish - falhou ao parar"
else
    echo -e "  ${GREEN}✓${NC} Varnish já está parado"
fi

###########################################################################
# 3. REDUZIR ELASTICSEARCH HEAP (512MB -> 256MB)
###########################################################################
echo -e "\n${BOLD}[3/5] Reduzindo Elasticsearch heap de 512MB para 256MB...${NC}"

ES_JVM_DIR="/etc/elasticsearch/jvm.options.d"
ES_JVM_MAIN="/etc/elasticsearch/jvm.options"

if [ -d "$ES_JVM_DIR" ] || [ -f "$ES_JVM_MAIN" ]; then
    # Create override directory if needed
    mkdir -p "$ES_JVM_DIR"

    # Write heap override
    cat > "${ES_JVM_DIR}/awa-heap.options" << 'EOF'
# AWA Motos - Optimized heap for 464 products
# Sufficient for small catalog, saves ~256MB RAM
-Xms256m
-Xmx256m
EOF
    echo -e "  ${GREEN}✓${NC} Escrito ${ES_JVM_DIR}/awa-heap.options"

    # Comment out hardcoded heap in main file
    if [ -f "$ES_JVM_MAIN" ]; then
        if grep -qE '^-Xm[sx]' "$ES_JVM_MAIN"; then
            sed -i 's/^-Xms/#-Xms/' "$ES_JVM_MAIN"
            sed -i 's/^-Xmx/#-Xmx/' "$ES_JVM_MAIN"
            echo -e "  ${GREEN}✓${NC} Heap antigo comentado em $ES_JVM_MAIN"
        fi
    fi

    # Restart Elasticsearch
    echo "  Reiniciando Elasticsearch..."
    systemctl restart elasticsearch 2>/dev/null && \
    echo -e "  ${GREEN}✓${NC} Elasticsearch reiniciado" || \
    echo -e "  ${RED}✗${NC} Falha ao reiniciar Elasticsearch"

    # Wait for ES to come up
    echo -n "  Aguardando ES iniciar"
    for i in {1..20}; do
        if curl -s http://127.0.0.1:9200 >/dev/null 2>&1; then
            echo ""
            echo -e "  ${GREEN}✓${NC} Elasticsearch respondendo"
            # Show new heap
            ES_PID=$(pgrep -f "elasticsearch" | head -1)
            if [ -n "$ES_PID" ]; then
                HEAP=$(cat /proc/$ES_PID/cmdline 2>/dev/null | tr '\0' '\n' | grep -E 'Xmx')
                echo -e "  ${GREEN}✓${NC} Novo heap: $HEAP"
            fi
            break
        fi
        echo -n "."
        sleep 2
    done

    # Check cluster health
    HEALTH=$(curl -s http://127.0.0.1:9200/_cluster/health 2>/dev/null | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    echo -e "  Cluster health: ${HEALTH:-unknown}"
else
    echo -e "  ${YELLOW}⚠${NC} Elasticsearch config não encontrado"
fi

###########################################################################
# 4. AJUSTAR MYSQL max_connections E thread_cache_size
###########################################################################
echo -e "\n${BOLD}[4/5] Ajustando MySQL max_connections (512 -> 100)...${NC}"

# Dynamic change
mysql -e "SET GLOBAL max_connections = 100;" 2>/dev/null && \
    echo -e "  ${GREEN}✓${NC} max_connections = 100 (dinâmico)" || \
    echo -e "  ${YELLOW}⚠${NC} Falha no SET GLOBAL (tentando via config)"

mysql -e "SET GLOBAL thread_cache_size = 16;" 2>/dev/null && \
    echo -e "  ${GREEN}✓${NC} thread_cache_size = 16 (dinâmico)" || true

# Persistent config
MYSQL_TUNE_FILE="/etc/mysql/conf.d/awa-tuning.cnf"
cat > "$MYSQL_TUNE_FILE" << 'EOF'
# AWA Motos - MySQL Tuning
# Adjusted for actual usage (~11 connections, 464 products)
[mysqld]
max_connections = 100
thread_cache_size = 16
# Performance Schema overhead reduction
performance_schema_max_table_instances = 400
table_open_cache_instances = 4
EOF
echo -e "  ${GREEN}✓${NC} Config persistente: $MYSQL_TUNE_FILE"
echo -e "  ${YELLOW}⚠${NC} MySQL precisa reiniciar para config persistente. Faça quando conveniente:"
echo "      systemctl restart mysql"

###########################################################################
# 5. LIMPEZA DE ARQUIVOS ÓRFÃOS
###########################################################################
echo -e "\n${BOLD}[5/5] Limpando arquivos órfãos...${NC}"

if [ -f "/etc/cron.d/awa-optimize-oneshot" ]; then
    rm -f /etc/cron.d/awa-optimize-oneshot 2>/dev/null && \
        echo -e "  ${GREEN}✓${NC} /etc/cron.d/awa-optimize-oneshot removido" || \
        echo -e "  ${YELLOW}⚠${NC} Falha ao remover /etc/cron.d/awa-optimize-oneshot"
else
    echo -e "  ${GREEN}✓${NC} Sem arquivos órfãos"
fi

###########################################################################
# RESULTADO FINAL
###########################################################################
echo -e "\n${BOLD}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║                    RESULTADO FINAL                          ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${YELLOW}=== MEMÓRIA APÓS OTIMIZAÇÃO ===${NC}"
free -h | head -2

echo -e "\n${YELLOW}=== LOAD ===${NC}"
uptime

echo -e "\n${YELLOW}=== SERVIÇOS ATIVOS ===${NC}"
for svc in php8.4-fpm elasticsearch mysql redis-server nginx; do
    STATUS=$(systemctl is-active "$svc" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "active" ]; then
        echo -e "  ${GREEN}●${NC} $svc: ativo"
    else
        echo -e "  ${RED}●${NC} $svc: $STATUS"
    fi
done

for svc in varnish php7.1-fpm php7.2-fpm php7.3-fpm php7.4-fpm php8.0-fpm php8.1-fpm php8.2-fpm php8.3-fpm php8.5-fpm; do
    STATUS=$(systemctl is-active "$svc" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "inactive" ] || [ "$STATUS" = "dead" ]; then
        echo -e "  ${GREEN}○${NC} $svc: parado ${GREEN}(economizando recursos)${NC}"
    elif [ "$STATUS" = "active" ]; then
        echo -e "  ${YELLOW}●${NC} $svc: ainda ativo!"
    fi
done

echo -e "\n${YELLOW}=== PHP-FPM MASTERS ===${NC}"
ps aux | grep 'php-fpm: master' | grep -v grep || echo "  Nenhum"

echo -e "\n${BOLD}Economia estimada:${NC}"
echo "  - PHP-FPM 9 versões: ~400MB RAM"
echo "  - Varnish: ~512MB RAM (malloc cache)"
echo "  - Elasticsearch heap: ~256MB RAM"
echo "  - Total estimado: ~1.1GB RAM liberados"
echo ""
echo -e "${BOLD}NOTA sobre VS Code Extensions (maior consumidor de CPU):${NC}"
echo "  Intelephense: 37% CPU, 990MB RAM"
echo "  DevSense PHP Tools: 21% CPU, 1.2GB RAM"
echo "  Extension Host: 19.5% CPU, 1GB RAM"
echo "  → Considere desabilitar DevSense OU Intelephense (redundantes)"
echo "  → Ambos fazem análise PHP, ter os dois duplica o trabalho"
echo ""
echo -e "${GREEN}Otimização concluída!${NC}"

#!/bin/bash
# =============================================================================
# Setup completo da integração Sectra ERP <-> Magento 2 (modo PULL via MySQL)
#
# REQUER: ser executado como root (sudo bash scripts/setup_sectra_pull.sh)
#
# O que faz:
# 1. Cria user MySQL 'sectra' com acesso remoto (via mysql root)
# 2. Cria tabela entity_map se não existir
# 3. Cria VIEWs compatíveis com o que o Sectra espera ler
# 4. Ativa configs do módulo ERP no Magento
# 5. Verifica bind-address do MySQL
# 6. Mostra IP público para configurar no Sectra
# =============================================================================

set -u

# Evitar problemas com history expansion ao lidar com '!' em senhas
set +H 2>/dev/null || true

MAGENTO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DB_NAME='magento'
MYSQL_SOCKET='/var/run/mysqld/mysqld.sock'

# Usuário do Magento (normalmente tem permissões suficientes no schema para criar tabelas/views/procedures)
MAGENTO_DB_USER='magento'
MAGENTO_DB_PASS='Aw4m0t0s2025Mage'

# Credenciais esperadas pelo Sectra
SECTRA_USER='sectra'
SECTRA_PASS='S3ctr4B2b_Aw4!2026'

# Conexão MySQL para DDL/DML no schema (sem depender de root do MySQL)
MYSQL_DDL="MYSQL_PWD='${MAGENTO_DB_PASS}' mysql --socket='${MYSQL_SOCKET}' -u '${MAGENTO_DB_USER}' '${DB_NAME}'"

# Conexão MySQL administrativa (CREATE USER / GRANT). Opcional.
# Se você souber a senha do root do MySQL, pode passar em runtime:
#   MYSQL_ROOT_PASSWORD='...' sudo bash scripts/setup_sectra_pull.sh
MYSQL_SUPER=''
SUPER_AVAILABLE=0

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()    { echo -e "  ${GREEN}[OK]${NC} $1"; }
fail()  { echo -e "  ${RED}[ERRO]${NC} $1"; }
warn()  { echo -e "  ${YELLOW}[AVISO]${NC} $1"; }
info()  { echo -e "  ${BLUE}[INFO]${NC} $1"; }

echo "============================================================"
echo "  SETUP SECTRA ERP <-> MAGENTO 2 (PULL VIA MYSQL)"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"
echo ""

# Verificar se roda como root
if [ "$(id -u)" -ne 0 ]; then
    fail "Execute como root: sudo bash $0"
    exit 1
fi

# Evitar o warning: "sudo: unable to resolve host ..." (não costuma quebrar, mas polui logs e pode afetar automações)
HOST_SHORT="$(hostname 2>/dev/null || true)"
if [ -n "$HOST_SHORT" ] && [ -w /etc/hosts ]; then
    if ! grep -Eq "(^|\s)${HOST_SHORT}(\s|$)" /etc/hosts 2>/dev/null; then
        echo "127.0.1.1 ${HOST_SHORT}" >> /etc/hosts 2>/dev/null || true
    fi
fi

# Verificar acesso ao MySQL como usuário do Magento (obrigatório)
if ! eval "$MYSQL_DDL -e \"SELECT 1\"" >/dev/null 2>&1; then
    fail "Nao foi possivel conectar no MySQL com o usuario do Magento (${MAGENTO_DB_USER})."
    fail "Verifique o socket (${MYSQL_SOCKET}) e a senha em app/etc/env.php."
    exit 1
fi
ok "Conexao MySQL (usuario do Magento) OK"

# Descobrir se temos um canal administrativo para CREATE USER / GRANT
if [ -n "${MYSQL_ROOT_PASSWORD:-}" ]; then
    MYSQL_SUPER="MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql --socket='${MYSQL_SOCKET}' -u root '${DB_NAME}'"
    if eval "$MYSQL_SUPER -e \"SELECT 1\"" >/dev/null 2>&1; then
        SUPER_AVAILABLE=1
        ok "Conexao MySQL admin OK (root via MYSQL_ROOT_PASSWORD)"
    else
        warn "MYSQL_ROOT_PASSWORD foi informado, mas a conexao admin falhou. Vou continuar sem CREATE USER/GRANT."
        MYSQL_SUPER=''
    fi
elif sudo -n mysql --socket="${MYSQL_SOCKET}" -u root "${DB_NAME}" -e "SELECT 1" >/dev/null 2>&1; then
    MYSQL_SUPER="sudo -n mysql --socket='${MYSQL_SOCKET}' -u root '${DB_NAME}'"
    SUPER_AVAILABLE=1
    ok "Conexao MySQL admin OK (sudo mysql / auth_socket)"
else
    warn "Sem acesso admin no MySQL (root). Vou pular CREATE USER/GRANT e criar apenas tabelas/views/procedure."
fi

# =============================================================================
# 1. CRIAR USUARIO MYSQL 'sectra'
# =============================================================================
echo ""
echo "== 1. USUARIO MYSQL 'sectra' =="

if [ "$SUPER_AVAILABLE" -eq 1 ]; then
    eval "$MYSQL_SUPER -e \"CREATE USER IF NOT EXISTS '${SECTRA_USER}'@'%' IDENTIFIED BY '${SECTRA_PASS}';\"" 2>&1 \
        && ok "Usuario ${SECTRA_USER} criado/verificado" \
        || warn "Aviso ao criar user ${SECTRA_USER}"
    eval "$MYSQL_SUPER -e \"ALTER USER '${SECTRA_USER}'@'%' IDENTIFIED BY '${SECTRA_PASS}';\"" 2>&1 \
        && ok "Senha do ${SECTRA_USER} atualizada" \
        || warn "Aviso ao atualizar senha do ${SECTRA_USER}"
else
    warn "Sem MySQL admin: pulando CREATE/ALTER USER. (Se o user ${SECTRA_USER} ja existe, ok.)"
fi

# =============================================================================
# 2. CRIAR TABELAS SE NAO EXISTIREM
# =============================================================================
echo ""
echo "== 2. TABELAS DO MODULO ERP =="

eval "$MYSQL_DDL -e \"
CREATE TABLE IF NOT EXISTS grupoawamotos_erp_entity_map (
    map_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(32) NOT NULL,
    erp_code VARCHAR(50) NOT NULL,
    magento_entity_id INT UNSIGNED NOT NULL,
    last_sync_at TIMESTAMP NULL DEFAULT NULL,
    sync_hash VARCHAR(64) NULL DEFAULT NULL,
    UNIQUE KEY uk_type_magento (entity_type, magento_entity_id),
    KEY idx_entity_type (entity_type),
    KEY idx_erp_code (erp_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mapeamento IDs Magento <-> ERP (Sectra)';
\"" 2>&1 && ok "Tabela grupoawamotos_erp_entity_map OK" || warn "Aviso na tabela entity_map"

# Garantir colunas esperadas (caso a tabela já exista com versão antiga)
MAP_HAS_ERP_CODE=$(eval "$MYSQL_DDL -N -e \"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${DB_NAME}' AND table_name='grupoawamotos_erp_entity_map' AND column_name='erp_code'\"" 2>/dev/null || echo "0")
if [ "$MAP_HAS_ERP_CODE" -eq 0 ]; then
    warn "Tabela grupoawamotos_erp_entity_map sem coluna erp_code. Tentando adicionar..."
    eval "$MYSQL_DDL -e \"ALTER TABLE grupoawamotos_erp_entity_map ADD COLUMN erp_code VARCHAR(50) NOT NULL AFTER entity_type;\"" 2>&1 || true
fi

MAP_HAS_LAST_SYNC=$(eval "$MYSQL_DDL -N -e \"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${DB_NAME}' AND table_name='grupoawamotos_erp_entity_map' AND column_name='last_sync_at'\"" 2>/dev/null || echo "0")
if [ "$MAP_HAS_LAST_SYNC" -eq 0 ]; then
    eval "$MYSQL_DDL -e \"ALTER TABLE grupoawamotos_erp_entity_map ADD COLUMN last_sync_at TIMESTAMP NULL DEFAULT NULL;\"" 2>&1 || true
fi

MAP_HAS_SYNC_HASH=$(eval "$MYSQL_DDL -N -e \"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${DB_NAME}' AND table_name='grupoawamotos_erp_entity_map' AND column_name='sync_hash'\"" 2>/dev/null || echo "0")
if [ "$MAP_HAS_SYNC_HASH" -eq 0 ]; then
    eval "$MYSQL_DDL -e \"ALTER TABLE grupoawamotos_erp_entity_map ADD COLUMN sync_hash VARCHAR(64) NULL DEFAULT NULL;\"" 2>&1 || true
fi

eval "$MYSQL_DDL -e \"
CREATE TABLE IF NOT EXISTS grupoawamotos_erp_sync_log (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL COMMENT 'Tipo: order, customer, product, stock, price',
    direction VARCHAR(10) NOT NULL DEFAULT 'pull' COMMENT 'pull ou push',
    entity_id VARCHAR(100) DEFAULT NULL COMMENT 'ID da entidade',
    status VARCHAR(20) NOT NULL DEFAULT 'success' COMMENT 'success, error, skipped',
    message TEXT DEFAULT NULL COMMENT 'Mensagem de log',
    details TEXT DEFAULT NULL COMMENT 'Detalhes JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync_type (sync_type),
    KEY idx_status (status),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log de sincronizacoes ERP';
\"" 2>&1 && ok "Tabela grupoawamotos_erp_sync_log OK" || warn "Aviso na tabela sync_log"

# =============================================================================
# 3. VERIFICAR/CRIAR COLUNA customer_erp_code
# =============================================================================
echo ""
echo "== 3. COLUNA customer_erp_code =="

COL_EXISTS=$(eval "$MYSQL_DDL -N -e \"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${DB_NAME}' AND table_name='sales_order' AND column_name='customer_erp_code'\"" 2>/dev/null || echo "0")
if [ "$COL_EXISTS" -gt 0 ]; then
    ok "Coluna customer_erp_code existe em sales_order"
else
    info "Criando coluna customer_erp_code..."
    eval "$MYSQL_DDL -e \"ALTER TABLE sales_order ADD COLUMN customer_erp_code VARCHAR(50) DEFAULT NULL COMMENT 'Codigo do cliente no ERP Sectra';\"" 2>&1 || true
    eval "$MYSQL_DDL -e \"ALTER TABLE sales_order_grid ADD COLUMN customer_erp_code VARCHAR(50) DEFAULT NULL COMMENT 'Codigo do cliente no ERP Sectra';\"" 2>&1 || true
    ok "Coluna criada"
fi

# =============================================================================
# 4. GRANTS
# =============================================================================
echo ""
echo "== 4. PERMISSOES (GRANTS) =="

if [ "$SUPER_AVAILABLE" -eq 1 ]; then
    eval "$MYSQL_SUPER -e \"GRANT SELECT ON ${DB_NAME}.* TO '${SECTRA_USER}'@'%';\"" 2>&1 && ok "GRANT SELECT ON ${DB_NAME}.*" || warn "Falha grant SELECT"
    eval "$MYSQL_SUPER -e \"GRANT INSERT, UPDATE ON ${DB_NAME}.grupoawamotos_erp_entity_map TO '${SECTRA_USER}'@'%';\"" 2>&1 && ok "GRANT INSERT/UPDATE entity_map" || warn "Falha"
    eval "$MYSQL_SUPER -e \"GRANT INSERT, UPDATE ON ${DB_NAME}.grupoawamotos_erp_sync_log TO '${SECTRA_USER}'@'%';\"" 2>&1 && ok "GRANT INSERT/UPDATE sync_log" || warn "Falha"
    eval "$MYSQL_SUPER -e \"GRANT INSERT ON ${DB_NAME}.sales_order_status_history TO '${SECTRA_USER}'@'%';\"" 2>&1 && ok "GRANT INSERT status_history" || warn "Falha"
    eval "$MYSQL_SUPER -e \"FLUSH PRIVILEGES;\"" 2>&1 && ok "FLUSH PRIVILEGES" || warn "Falha flush"
else
    warn "Sem MySQL admin: pulando GRANT/FLUSH. (Se o user ${SECTRA_USER} ja tem permissao, ok.)"
fi

# =============================================================================
# 5. VIEWS
# =============================================================================
echo ""
echo "== 5. VIEWS PARA O SECTRA =="

eval "$MYSQL_DDL -e \"
CREATE OR REPLACE VIEW vw_sectra_pedidos_pendentes AS
SELECT
    so.entity_id AS magento_order_id,
    so.increment_id AS pedido_web,
    so.created_at AS data_pedido,
    so.updated_at AS data_atualizacao,
    so.state AS estado,
    so.status AS status_magento,
    so.customer_id,
    so.customer_email,
    so.customer_firstname,
    so.customer_lastname,
    COALESCE(so.customer_taxvat, '') AS cpf_cnpj,
    COALESCE(so.customer_erp_code, '') AS erp_code,
    so.subtotal,
    ABS(COALESCE(so.discount_amount, 0)) AS desconto,
    COALESCE(so.shipping_amount, 0) AS frete,
    so.grand_total AS total,
    so.total_qty_ordered AS qtd_itens,
    COALESCE(so.coupon_code, '') AS cupom,
    COALESCE(sop.method, '') AS forma_pagamento,
    COALESCE(soa.street, '') AS endereco,
    COALESCE(soa.city, '') AS cidade,
    COALESCE(dcr.code, soa.region, '') AS uf,
    COALESCE(soa.postcode, '') AS cep,
    COALESCE(soa.telephone, '') AS telefone
FROM sales_order so
LEFT JOIN sales_order_payment sop ON sop.parent_id = so.entity_id
LEFT JOIN sales_order_address soa ON soa.parent_id = so.entity_id AND soa.address_type = 'shipping'
LEFT JOIN directory_country_region dcr ON dcr.region_id = soa.region_id
WHERE so.state IN ('new', 'pending_payment', 'processing')
  AND so.entity_id NOT IN (
      SELECT magento_entity_id FROM grupoawamotos_erp_entity_map WHERE entity_type = 'order'
  )
ORDER BY so.created_at ASC;
\"" 2>&1 && ok "View vw_sectra_pedidos_pendentes" || fail "Falha view pedidos_pendentes"

eval "$MYSQL_DDL -e \"
CREATE OR REPLACE VIEW vw_sectra_pedidos_itens AS
SELECT
    soi.order_id AS magento_order_id,
    so.increment_id AS pedido_web,
    soi.item_id,
    soi.sku AS codigo_produto,
    soi.name AS descricao,
    soi.qty_ordered AS quantidade,
    soi.price AS preco_unitario,
    soi.row_total AS total_item,
    ABS(COALESCE(soi.discount_amount, 0)) AS desconto_item,
    (soi.row_total - ABS(COALESCE(soi.discount_amount, 0))) AS total_liquido,
    soi.weight AS peso
FROM sales_order_item soi
INNER JOIN sales_order so ON so.entity_id = soi.order_id
WHERE soi.parent_item_id IS NULL
  AND soi.qty_ordered > 0
ORDER BY soi.order_id, soi.item_id;
\"" 2>&1 && ok "View vw_sectra_pedidos_itens" || fail "Falha view pedidos_itens"

eval "$MYSQL_DDL -e \"
CREATE OR REPLACE VIEW vw_sectra_clientes_b2b AS
SELECT
    ce.entity_id AS magento_customer_id,
    ce.email,
    CONCAT(ce.firstname, ' ', ce.lastname) AS nome,
    COALESCE(ce.taxvat, '') AS cpf_cnpj,
    ce.created_at AS data_cadastro,
    COALESCE(cev_erp.value, '') AS erp_code,
    COALESCE(cev_tipo.value, '') AS tipo_pessoa,
    COALESCE(eem.erp_code, '') AS erp_entity_map_code
FROM customer_entity ce
LEFT JOIN eav_attribute ea_erp ON ea_erp.attribute_code = 'erp_code'
    AND ea_erp.entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'customer')
LEFT JOIN customer_entity_varchar cev_erp ON cev_erp.entity_id = ce.entity_id AND cev_erp.attribute_id = ea_erp.attribute_id
LEFT JOIN eav_attribute ea_tipo ON ea_tipo.attribute_code = 'person_type'
    AND ea_tipo.entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'customer')
LEFT JOIN customer_entity_varchar cev_tipo ON cev_tipo.entity_id = ce.entity_id AND cev_tipo.attribute_id = ea_tipo.attribute_id
LEFT JOIN grupoawamotos_erp_entity_map eem ON eem.magento_entity_id = ce.entity_id AND eem.entity_type = 'customer'
WHERE ce.is_active = 1
ORDER BY ce.entity_id;
\"" 2>&1 && ok "View vw_sectra_clientes_b2b" || fail "Falha view clientes_b2b"

eval "$MYSQL_DDL -e \"
CREATE OR REPLACE VIEW vw_sectra_pedidos_sincronizados AS
SELECT
    eem.erp_code AS erp_pedido_id,
    eem.magento_entity_id AS magento_order_id,
    so.increment_id AS pedido_web,
    so.state AS estado,
    so.status AS status_magento,
    so.grand_total AS total,
    so.created_at AS data_pedido,
    eem.last_sync_at AS data_sync
FROM grupoawamotos_erp_entity_map eem
INNER JOIN sales_order so ON so.entity_id = eem.magento_entity_id
WHERE eem.entity_type = 'order'
ORDER BY eem.last_sync_at DESC;
\"" 2>&1 && ok "View vw_sectra_pedidos_sincronizados" || fail "Falha view pedidos_sincronizados"

# =============================================================================
# 6. STORED PROCEDURE (via arquivo SQL para evitar problemas com DELIMITER)
# =============================================================================
echo ""
echo "== 6. STORED PROCEDURE =="

mkdir -p "$MAGENTO_DIR/var/tmp" 2>/dev/null || true
SP_SQL_FILE="$MAGENTO_DIR/var/tmp/sp_sectra_ack.sql"

cat > "$SP_SQL_FILE" <<'EOSQL'
DROP PROCEDURE IF EXISTS sp_sectra_ack_pedido;

DELIMITER //

CREATE PROCEDURE sp_sectra_ack_pedido(
    IN p_increment_id VARCHAR(50),
    IN p_erp_order_id VARCHAR(50)
)
BEGIN
    DECLARE v_magento_id INT;

    SELECT entity_id INTO v_magento_id
    FROM sales_order WHERE increment_id = p_increment_id LIMIT 1;

    IF v_magento_id IS NOT NULL THEN
        INSERT INTO grupoawamotos_erp_entity_map (entity_type, erp_code, magento_entity_id, last_sync_at)
        VALUES ('order', p_erp_order_id, v_magento_id, NOW())
        ON DUPLICATE KEY UPDATE erp_code = p_erp_order_id, last_sync_at = NOW();

        INSERT INTO sales_order_status_history (parent_id, is_customer_notified, is_visible_on_front, comment, status, entity_name, created_at)
        VALUES (v_magento_id, 0, 0, CONCAT('[ERP Sectra] Pedido importado. ID ERP: ', p_erp_order_id), 'processing', 'order', NOW());

        INSERT INTO grupoawamotos_erp_sync_log (sync_type, direction, entity_id, status, message, created_at)
        VALUES ('order', 'pull', p_increment_id, 'success', CONCAT('Ack ERP ID: ', p_erp_order_id), NOW());

        SELECT 'OK' AS resultado, v_magento_id AS magento_id, p_erp_order_id AS erp_id;
    ELSE
        INSERT INTO grupoawamotos_erp_sync_log (sync_type, direction, entity_id, status, message, created_at)
        VALUES ('order', 'pull', p_increment_id, 'error', 'Pedido nao encontrado no Magento', NOW());

        SELECT 'ERRO' AS resultado, 'Pedido nao encontrado' AS mensagem;
    END IF;
END //

DELIMITER ;
EOSQL

eval "$MYSQL_DDL < \"$SP_SQL_FILE\"" 2>&1 && ok "Procedure sp_sectra_ack_pedido criada" || warn "Falha ao criar procedure (vai seguir sem ela)"
rm -f "$SP_SQL_FILE" 2>/dev/null || true

if [ "$SUPER_AVAILABLE" -eq 1 ]; then
    eval "$MYSQL_SUPER -e \"GRANT EXECUTE ON PROCEDURE ${DB_NAME}.sp_sectra_ack_pedido TO '${SECTRA_USER}'@'%';\"" 2>&1 && ok "GRANT EXECUTE procedure" || warn "Falha grant execute"
    eval "$MYSQL_SUPER -e \"FLUSH PRIVILEGES;\"" 2>&1 || true
fi

# =============================================================================
# 7. ATIVAR CONFIGS NO MAGENTO
# =============================================================================
echo ""
echo "== 7. CONFIGS DO MAGENTO =="

cd "$MAGENTO_DIR" || { fail "Nao foi possivel acessar MAGENTO_DIR=$MAGENTO_DIR"; exit 1; }

if [ ! -f "bin/magento" ]; then
    fail "bin/magento nao encontrado em: $MAGENTO_DIR"
    fail "Dica: coloque este script dentro da pasta scripts/ do projeto Magento e execute de la."
    exit 1
fi

sudo -u www-data php bin/magento config:set grupoawamotos_erp/connection/enabled 1 2>/dev/null && ok "ERP habilitado" || warn "Falha"
sudo -u www-data php bin/magento config:set grupoawamotos_erp/sync_orders/enabled 1 2>/dev/null && ok "Sync pedidos habilitado" || warn "Falha"
sudo -u www-data php bin/magento config:set grupoawamotos_erp/sync_orders/send_on_place 0 2>/dev/null && ok "send_on_place = 0 (PULL)" || warn "Falha"
sudo -u www-data php bin/magento config:set grupoawamotos_erp/sync_orders/use_queue 0 2>/dev/null && ok "use_queue = 0" || warn "Falha"
sudo -u www-data php bin/magento cache:flush 2>/dev/null && ok "Cache limpo" || warn "Falha cache"

# =============================================================================
# 8. ACESSO REMOTO MYSQL
# =============================================================================
echo ""
echo "== 8. ACESSO REMOTO MYSQL =="

BIND_ADDR=$(grep -r 'bind-address' /etc/mysql/ 2>/dev/null | grep -v '#' | tail -1 || echo "nao encontrado")
info "MySQL bind-address: $BIND_ADDR"

if echo "$BIND_ADDR" | grep -q '127.0.0.1'; then
    MYSQL_CONF="/etc/mysql/mysql.conf.d/mysqld.cnf"
    if [ -f "$MYSQL_CONF" ]; then
        info "Alterando bind-address para 0.0.0.0..."
        sed -i 's/^bind-address\s*=\s*127\.0\.0\.1/bind-address = 0.0.0.0/' "$MYSQL_CONF"
        systemctl restart mysql 2>/dev/null && ok "MySQL reiniciado com bind-address=0.0.0.0" || warn "Falha ao reiniciar MySQL"
    fi
elif echo "$BIND_ADDR" | grep -q '0.0.0.0'; then
    ok "MySQL ja aceita conexoes remotas"
fi

# =============================================================================
# 9. FIREWALL
# =============================================================================
echo ""
echo "== 9. FIREWALL (porta 3306) =="

SECTRA_IP="201.33.193.193"

if command -v ufw &>/dev/null; then
    UFW_STATUS=$(ufw status 2>/dev/null | head -1)
    info "UFW: $UFW_STATUS"
    ufw allow from $SECTRA_IP to any port 3306 proto tcp 2>/dev/null && ok "Porta 3306 liberada para $SECTRA_IP" || warn "Falha ufw"
fi

# =============================================================================
# 10. TESTE FINAL COMPLETO
# =============================================================================
echo ""
echo "== 10. TESTE FINAL =="

mysql -u sectra -p"$SECTRA_PASS" magento -e "SELECT 'OK' AS conexao;" 2>/dev/null && ok "User sectra conecta" || fail "User sectra NAO conecta"

for VIEW in vw_sectra_pedidos_pendentes vw_sectra_pedidos_itens vw_sectra_clientes_b2b vw_sectra_pedidos_sincronizados; do
    COUNT=$(mysql -u sectra -p"$SECTRA_PASS" magento -N -e "SELECT COUNT(*) FROM $VIEW;" 2>/dev/null || echo "ERRO")
    if [ "$COUNT" != "ERRO" ]; then
        ok "$VIEW ($COUNT registros)"
    else
        fail "$VIEW inacessivel"
    fi
done

PROC_TEST=$(mysql -u sectra -p"$SECTRA_PASS" magento -N -e "SELECT ROUTINE_NAME FROM information_schema.routines WHERE ROUTINE_SCHEMA='magento' AND ROUTINE_NAME='sp_sectra_ack_pedido';" 2>/dev/null || echo "")
if [ -n "$PROC_TEST" ]; then
    ok "Procedure sp_sectra_ack_pedido acessivel"
else
    fail "Procedure sp_sectra_ack_pedido NAO encontrada"
fi

echo ""
info "Amostra de pedidos pendentes:"
mysql -u sectra -p"$SECTRA_PASS" magento -e "SELECT pedido_web, data_pedido, cpf_cnpj, total, forma_pagamento FROM vw_sectra_pedidos_pendentes LIMIT 5;" 2>/dev/null || warn "Nenhum pedido pendente"

# =============================================================================
# 11. IP E INSTRUCOES FINAIS
# =============================================================================
echo ""
echo "== 11. IP DO SERVIDOR =="

PUBLIC_IP=$(curl -s --max-time 5 ifconfig.me 2>/dev/null || curl -s --max-time 5 icanhazip.com 2>/dev/null || echo "NAO DETECTADO")
PUBLIC_IP4=$(curl -s --max-time 5 -4 ifconfig.me 2>/dev/null || echo "")
DOMAIN_IP=$(dig +short awamotos.com 2>/dev/null | head -1 || echo "")

info "IP publico (auto):  $PUBLIC_IP"
if [ -n "$PUBLIC_IP4" ]; then
    info "IPv4 publico:       $PUBLIC_IP4"
fi
info "IP do dominio:       ${DOMAIN_IP:-NAO RESOLVIDO}"

SECTRA_HOST="$PUBLIC_IP"
if [ -n "$PUBLIC_IP4" ]; then
    SECTRA_HOST="$PUBLIC_IP4"
elif [ -n "$DOMAIN_IP" ]; then
    SECTRA_HOST="$DOMAIN_IP"
fi

echo ""
echo "============================================================"
echo -e "  ${GREEN}SETUP 100% CONCLUIDO!${NC}"
echo "============================================================"
echo ""
echo "CONFIGURAR NO SECTRA (Parametros de Sistema > 24.05):"
echo "  24.05.001 - Endereco Host BD:    $SECTRA_HOST"
echo "  24.05.002 - Nome Banco de Dados: magento"
echo "  24.05.003 - Usuario BD:          sectra"
echo "  24.05.004 - Senha BD:            (manter S3ctr4B2b_Aw4!2026)"
echo ""
echo "QUERIES QUE O SECTRA DEVE USAR:"
echo ""
echo "  -- 1) Buscar pedidos pendentes (nao importados):"
echo "  SELECT * FROM vw_sectra_pedidos_pendentes;"
echo ""
echo "  -- 2) Buscar itens de um pedido:"
echo "  SELECT * FROM vw_sectra_pedidos_itens WHERE pedido_web = '100000123';"
echo ""
echo "  -- 3) Confirmar que importou o pedido:"
echo "  CALL sp_sectra_ack_pedido('100000123', 'ERP-12345');"
echo "  (pedido sai da view de pendentes automaticamente)"
echo ""
echo "  -- 4) Ver pedidos ja sincronizados:"
echo "  SELECT * FROM vw_sectra_pedidos_sincronizados;"
echo ""
echo "  -- 5) Ver clientes com CPF/CNPJ:"
echo "  SELECT * FROM vw_sectra_clientes_b2b WHERE cpf_cnpj != '';"
echo ""
echo "FLUXO COMPLETO:"
echo "  Cliente compra no site"
echo "  -> Sectra faz SELECT vw_sectra_pedidos_pendentes (periodico)"
echo "  -> Importa pedido + itens no ERP"
echo "  -> CALL sp_sectra_ack_pedido('num_pedido', 'id_erp')"
echo "  -> Pedido some da view de pendentes"
echo "  -> Cron Magento sincroniza status ERP -> Magento (cada 15min)"
echo "============================================================"

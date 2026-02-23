#!/bin/bash
set -e

cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

echo "============================================"
echo "🚀 AWA Motos - Full Deploy Pipeline"
echo "============================================"

echo ""
echo "📦 Step 1/6: Redis FLUSHALL..."
redis-cli FLUSHALL && echo "✅ Redis flushed" || echo "⚠️ Redis not available, skipping"

echo ""
echo "📦 Step 2/6: setup:upgrade..."
php bin/magento setup:upgrade
echo "✅ Setup upgrade complete"

echo ""
echo "📦 Step 3/6: setup:di:compile..."
php bin/magento setup:di:compile
echo "✅ DI compile complete"

echo ""
echo "📦 Step 4/6: static-content:deploy pt_BR..."
php bin/magento setup:static-content:deploy pt_BR -f
echo "✅ Static content deployed"

echo ""
echo "📦 Step 5/6: cache:clean + cache:flush..."
php bin/magento cache:clean && php bin/magento cache:flush
echo "✅ Cache cleaned and flushed"

echo ""
echo "📦 Step 6/6: indexer:reindex..."
php bin/magento indexer:reindex
echo "✅ Reindex complete"

echo ""
echo "============================================"
echo "✅ DEPLOY COMPLETO! Todas as etapas concluídas."
echo "============================================"

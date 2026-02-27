#!/bin/bash
# Deploy Homepage Performance Fix — content-visibility scoping
# Commit: bbef4fbb (feat/visual-clean-final)
# Executar como: bash scripts/deploy-homepage-perf.sh

set -e
cd "$(dirname "$0")/.."

echo "=========================================="
echo " AWA Motos — Deploy Homepage Performance"
echo "=========================================="
echo ""

# 1. Push
echo "➤ [1/7] Git push..."
git push origin feat/visual-clean-final
echo "✅ Push OK"
echo ""

# 2. Fix permissions
echo "➤ [2/7] Fix config.php permissions..."
sudo chmod 664 app/etc/config.php
echo "✅ Permissions OK"
echo ""

# 3. setup:upgrade (aplica data patches dos CMS blocks)
echo "➤ [3/7] setup:upgrade..."
sudo -u www-data php bin/magento setup:upgrade
echo "✅ Upgrade OK"
echo ""

# 4. DI compile
echo "➤ [4/7] di:compile..."
sudo -u www-data php bin/magento setup:di:compile
echo "✅ Compile OK"
echo ""

# 5. Static content deploy
echo "➤ [5/7] static-content:deploy pt_BR..."
sudo -u www-data php bin/magento setup:static-content:deploy pt_BR -f
echo "✅ Deploy OK"
echo ""

# 6. Cache flush
echo "➤ [6/7] cache:flush..."
sudo -u www-data php bin/magento cache:flush
echo "✅ Cache OK"
echo ""

# 7. Validar slider no banco
echo "➤ [7/7] Validar slider 'homepageslider'..."
mysql -u magento -p'Aw4m0t0s2025Mage' magento -e "
  SELECT slider_id, slider_identifier, slider_title, slider_status
  FROM rokanthemes_slider
  WHERE slider_identifier='homepageslider'
  LIMIT 1;
" 2>/dev/null || echo "⚠️  Tabela rokanthemes_slider não encontrada (verifique se o módulo SlideBanner está ativo)"
echo ""

echo "=========================================="
echo " ✅ DEPLOY COMPLETO!"
echo " Verifique: https://awamotos.com"
echo "=========================================="

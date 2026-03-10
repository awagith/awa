#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

required_files=(
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/tokens.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/base.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/layout.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/awa-layout-semantic.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/header.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/typography.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/forms.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/buttons.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/carousel.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/navigation.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/messages-alerts.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/badges-status.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/tables.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/product-cards.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components/b2b-panels.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/utilities.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/a11y.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/responsive.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/legacy-bridge.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/footer.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/themeoption-safety.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/b2b/pages/cart-refine.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/pages/home.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/pages/plp-search.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/pages/pdp.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/pages/cart-checkout.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/pages/account-b2b.css"
)

for file in "${required_files[@]}"; do
  if [[ ! -f "$file" ]]; then
    echo "Missing required layer file: $file"
    exit 1
  fi
done

check_ref() {
  local file="$1"
  local pattern="$2"
  if ! rg -q --fixed-strings "$pattern" "$file"; then
    echo "Missing reference '$pattern' in $file"
    exit 1
  fi
}

check_absent_css_src() {
  local file="$1"
  local src="$2"
  if rg -q "<css[^>]+src=[\"']$src[\"']" "$file"; then
    echo "Legacy CSS src '$src' must not be loaded in $file"
    exit 1
  fi
}

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/tokens.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/base.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/layout.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/awa-layout-semantic.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/header.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/typography.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/forms.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/buttons.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/carousel.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/navigation.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/messages-alerts.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/badges-status.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/tables.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/product-cards.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components/b2b-panels.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/utilities.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/a11y.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/responsive.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/legacy-bridge.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/footer.css"

check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-core.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-layout.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-components.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-consistency.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-consistency-ui.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-fixes.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/awa-grid-unified.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/layers/pages/home.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/awa-fixes-home.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/awa-design-system-layout.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/awa-custom-home-final-polish.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/awa-consistency-home5.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_category_view.xml" "css/layers/pages/plp-search.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_CatalogSearch/layout/catalogsearch_result_index.xml" "css/layers/pages/plp-search.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_category_view.xml" "css/awa-custom-plp-final-polish.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_category_view.xml" "css/b2b/pages/plp-search-cart-refine.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_CatalogSearch/layout/catalogsearch_result_index.xml" "css/awa-custom-plp-final-polish.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_CatalogSearch/layout/catalogsearch_result_index.xml" "css/b2b/pages/plp-search-cart-refine.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_product_view.xml" "css/layers/pages/pdp.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_product_view.xml" "css/awa-custom-pdp-conversion.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_product_view.xml" "css/awa-custom-pdp-final-polish.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/layers/pages/cart-checkout.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/b2b/pages/cart-refine.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/b2b/pages/cart-checkout-premium.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/awa-checkout-home5.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_index_index.xml" "css/layers/pages/cart-checkout.css"
check_absent_css_src "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_index_index.xml" "css/awa-checkout-home5.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Customer/layout/customer_account.xml" "css/layers/pages/account-b2b.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/GrupoAwamotos_B2B/layout/b2b_auth_shell.xml" "css/layers/pages/account-b2b.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/GrupoAwamotos_B2B/layout/customer_account.xml" "css/layers/pages/account-b2b.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/GrupoAwamotos_B2B/layout/b2b_account_dashboard.xml" "css/layers/pages/account-b2b.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Rokanthemes_Themeoption/templates/html/head.phtml" "css/layers/themeoption-safety.css"
if rg -q --fixed-strings "css/awa-rokanthemes-head-inline.css" "app/design/frontend/AWA_Custom/ayo_home5_child/Rokanthemes_Themeoption/templates/html/head.phtml"; then
  echo "Head override leakage: head.phtml must not load css/awa-rokanthemes-head-inline.css"
  exit 1
fi

plp_bundle="app/design/frontend/AWA_Custom/ayo_home5_child/web/css/b2b/pages/plp-search-cart-refine.css"
cart_bundle="app/design/frontend/AWA_Custom/ayo_home5_child/web/css/b2b/pages/cart-refine.css"

if rg -q 'body\.checkout-cart-index' "$plp_bundle"; then
  echo "Route leakage: checkout-cart selectors must not exist in $plp_bundle"
  exit 1
fi

if rg -q 'catalog-category-view|catalogsearch-result-index' "$cart_bundle"; then
  echo "Route leakage: PLP/Search selectors must not exist in $cart_bundle"
  exit 1
fi

if rg -q --fixed-strings 'css/b2b/pages/plp-search-cart-refine.css' "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml"; then
  echo "Route leakage: checkout_cart_index.xml must not load plp-search-cart-refine.css"
  exit 1
fi

echo "OK: CSS layer architecture is consistent."

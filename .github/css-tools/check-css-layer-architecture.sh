#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

required_files=(
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/tokens.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/base.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/components.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/utilities.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/legacy-bridge.css"
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

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/tokens.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/base.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/components.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/utilities.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Theme/layout/default_head_blocks.xml" "css/layers/legacy-bridge.css"

check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml" "css/layers/pages/home.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_category_view.xml" "css/layers/pages/plp-search.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_CatalogSearch/layout/catalogsearch_result_index.xml" "css/layers/pages/plp-search.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Catalog/layout/catalog_product_view.xml" "css/layers/pages/pdp.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/layers/pages/cart-checkout.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Checkout/layout/checkout_cart_index.xml" "css/b2b/pages/cart-refine.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/GrupoAwamotos_B2B/layout/b2b_auth_shell.xml" "css/layers/pages/account-b2b.css"
check_ref "app/design/frontend/AWA_Custom/ayo_home5_child/Rokanthemes_Themeoption/templates/html/head.phtml" "css/layers/themeoption-safety.css"

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

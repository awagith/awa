#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

FORBIDDEN_PATTERN='(#111827|#1f2937|#334155|#475569|#64748b)'

TARGETS=(
  "app/code/GrupoAwamotos/B2B/view/frontend/web/css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/b2b"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/awa-custom-global-brand.css"
  "app/design/frontend/AWA_Custom/ayo_home5_child/web/css/awa-design-system-layout.css"
)

echo "Checking forbidden legacy color literals in B2B CSS..."

if rg -n -S -i "$FORBIDDEN_PATTERN" "${TARGETS[@]}" --glob '*.css' --glob '*.less'; then
  echo
  echo "Failed: found forbidden legacy colors."
  echo "Use AWA tokens instead (e.g. var(--awa-text-primary) / var(--awa-text-muted))."
  exit 1
fi

echo "OK: no forbidden legacy color literals found."

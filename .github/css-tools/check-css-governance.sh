#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

STYLELINT_CMD=(node node_modules/stylelint/bin/stylelint.mjs)

BASE_SHA="${BASE_SHA:-}"
if [[ -z "$BASE_SHA" ]]; then
  if git rev-parse --verify origin/main >/dev/null 2>&1; then
    BASE_SHA="origin/main"
  else
    BASE_SHA="HEAD~1"
  fi
fi

if ! git rev-parse --verify "$BASE_SHA" >/dev/null 2>&1; then
  echo "Warning: invalid BASE_SHA '$BASE_SHA'. Falling back to HEAD~1"
  BASE_SHA="HEAD~1"
fi

mapfile -t changed_css < <(git diff --name-only "$BASE_SHA"...HEAD -- '*.css' '*.less')

if [[ ${#changed_css[@]} -eq 0 ]]; then
  echo "No CSS/LESS changes detected."
  exit 0
fi

"${STYLELINT_CMD[@]}" --config .stylelintrc.css-parse.json --allow-empty-input "${changed_css[@]}"

governed=()
for file in "${changed_css[@]}"; do
  case "$file" in
    app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/*|\
    app/design/frontend/AWA_Custom/ayo_home5_child/web/css/b2b/*|\
    app/code/GrupoAwamotos/B2B/view/frontend/web/css/*)
      governed+=("$file")
      ;;
  esac
done

if [[ ${#governed[@]} -gt 0 ]]; then
  "${STYLELINT_CMD[@]}" --config .stylelintrc.css-duplicates.json --allow-empty-input "${governed[@]}"
fi

important_fail=0
for file in "${changed_css[@]}"; do
  [[ -f "$file" ]] || continue

  case "$file" in
    app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/themeoption-safety.css)
      continue
      ;;
  esac

  head_count=$(rg -o '!important' "$file" | wc -l | tr -d ' ')
  base_count=0

  if git cat-file -e "$BASE_SHA:$file" 2>/dev/null; then
    base_count=$(git show "$BASE_SHA:$file" | rg -o '!important' | wc -l | tr -d ' ')
  fi

  if (( head_count > base_count )); then
    echo "!important budget exceeded in $file (base=$base_count, head=$head_count)"
    important_fail=1
  fi
done

hex_fail=0
token_allow_regex='app/design/frontend/AWA_Custom/ayo_home5_child/web/css/source/_awa-(variables|tokens)\.less|app/design/frontend/AWA_Custom/ayo_home5_child/web/css/layers/tokens\.css'

for file in "${changed_css[@]}"; do
  if [[ "$file" =~ $token_allow_regex ]]; then
    continue
  fi

  if git diff -U0 "$BASE_SHA"...HEAD -- "$file" \
    | awk '/^\+[^+]/ {print substr($0,2)}' \
    | rg -n '#[0-9A-Fa-f]{3,8}\b' >/tmp/css_hex_new.txt; then
    echo "New hardcoded hex detected in $file:"
    cat /tmp/css_hex_new.txt
    hex_fail=1
  fi
done

if (( important_fail != 0 || hex_fail != 0 )); then
  exit 1
fi

echo "OK: CSS governance checks passed."

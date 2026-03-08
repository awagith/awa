#!/bin/bash
# ==========================================================================
# fix-xml-case.sh — Reverte corrupção de formatador XML em arquivos Magento
#
# Problema: Extensões do VS Code (Gemini, Qwen, webvalidator) convertem
# tags XML do Magento para lowercase e adicionam ";" em xmlns e atributos.
# Magento layout/DI XML é CASE-SENSITIVE:
#   referenceBlock ≠ referenceblock (lowercase = erro fatal)
#   virtualType ≠ virtualtype (lowercase = schema violation)
#
# Uso:
#   ./bin/fix-xml-case.sh --check [arquivo.xml]   # apenas verifica (exit 1 se corrompido)
#   ./bin/fix-xml-case.sh --fix [arquivo.xml]     # corrige explicitamente, sob demanda
#
# Integração:
#   - Git pre-commit hook: .git/hooks/pre-commit
#   - Cron: */5 * * * * /path/to/fix-xml-case.sh >> /var/log/xml-fix.log 2>&1
# ==========================================================================

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MODE="check"
FIXED=0
ERRORS=0

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[XML-FIX]${NC} $*"; }
warn() { echo -e "${YELLOW}[XML-FIX]${NC} $*"; }
err() { echo -e "${RED}[XML-FIX]${NC} $*" >&2; }

# All corruption patterns to detect
CORRUPTION_PATTERN='nonamespaceschemalocation|<referenceblock |<\/referenceblock>|<referencecontainer |<\/referencecontainer>|<virtualtype |<\/virtualtype>|<argument [^>]*";|<item [^>]*";|<column;|<page;|xmlns:xsi="[^"]*";|sortorder='

validate_xml() {
    local file="$1"

    php -r '
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $ok = $dom->load($argv[1]);
        if (!$ok) {
            foreach (libxml_get_errors() as $error) {
                fwrite(STDERR, trim($error->message) . PHP_EOL);
            }
            exit(1);
        }
    ' "$file"
}

is_corrupted() {
    local file="$1"

    if grep -qE "$CORRUPTION_PATTERN" "$file" 2>/dev/null; then
        return 0
    fi

    if ! validate_xml "$file" >/dev/null 2>&1; then
        return 0
    fi

    return 1
}

fix_file() {
    local file="$1"

    # Skip if file doesn't exist or is not XML
    [[ ! -f "$file" ]] && return 0
    [[ "$file" != *.xml ]] && return 0

    # Check for corruption patterns (case-sensitive: only match WRONG casing)
    if is_corrupted "$file"; then
        if [[ "$MODE" == "check" ]]; then
            err "CORRUPTED: $file"
            return 1
        fi

        if [[ ! -w "$file" ]]; then
            err "READ-ONLY: $file"
            return 1
        fi

        # ── Fix xmlns semicolons ──────────────────────────────────────
        sed -i 's/xmlns:xsi="http:\/\/www.w3.org\/2001\/XMLSchema-instance";/xmlns:xsi="http:\/\/www.w3.org\/2001\/XMLSchema-instance"/g' "$file"

        # ── Fix noNamespaceSchemaLocation casing ──────────────────────
        sed -i 's/xsi:nonamespaceschemalocation=/xsi:noNamespaceSchemaLocation=/g' "$file"

        # ── Fix layout XML tags (case-sensitive) ──────────────────────
        sed -i 's/<referenceblock /<referenceBlock /g' "$file"
        sed -i 's/<\/referenceblock>/<\/referenceBlock>/g' "$file"
        sed -i 's/<referenceblock\//<referenceBlock\//g' "$file"

        sed -i 's/<referencecontainer /<referenceContainer /g' "$file"
        sed -i 's/<\/referencecontainer>/<\/referenceContainer>/g' "$file"

        # ── Fix DI XML tags (case-sensitive) ──────────────────────────
        sed -i 's/<virtualtype /<virtualType /g' "$file"
        sed -i 's/<\/virtualtype>/<\/virtualType>/g' "$file"

        # ── Fix semicolons in attribute values ────────────────────────
        # Pattern: name="value"; xsi:type → name="value" xsi:type
        sed -i 's/\(name="[^"]*"\); \(xsi:type\)/\1 \2/g' "$file"

        # Pattern: <column; → <column
        sed -i 's/<column;/<column/g' "$file"

        # Pattern: <page; → <page
        sed -i 's/<page;/<page/g' "$file"

        # ── Fix semicolons after any attribute before xsi:type ────────
        # Catches: name="handlers"; xsi:type  →  name="handlers" xsi:type
        sed -i 's/"; xsi:type/" xsi:type/g' "$file"

        sed -i 's/sortorder=/sortOrder=/g' "$file"

        if ! validate_xml "$file"; then
            err "INVALID AFTER FIX: $file"
            return 1
        fi

        log "FIXED: $file"
        ((FIXED++))
    fi
}

# Parse arguments
if [[ "${1:-}" == "--check" ]]; then
    MODE="check"
    shift
elif [[ "${1:-}" == "--fix" ]]; then
    MODE="fix"
    shift
fi

# Target files
if [[ $# -gt 0 ]]; then
    # Specific files
    for f in "$@"; do
        fix_file "$f" || ((ERRORS++))
    done
else
    # All custom module XMLs
    while IFS= read -r -d '' file; do
        fix_file "$file" || ((ERRORS++))
    done < <(find "$PROJECT_ROOT/app/code" "$PROJECT_ROOT/app/design" -name '*.xml' -print0 2>/dev/null)
fi

if [[ "$MODE" == "check" ]]; then
    if [[ $ERRORS -gt 0 ]]; then
        err "$ERRORS corrupted XML file(s) found. Run: ./bin/fix-xml-case.sh --fix [arquivo.xml]"
        exit 1
    else
        log "All XML files OK"
        exit 0
    fi
fi

if [[ $FIXED -gt 0 ]]; then
    log "Fixed $FIXED file(s)"
fi

exit 0

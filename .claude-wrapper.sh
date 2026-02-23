#!/usr/bin/env bash
set -euo pipefail

# Claude Code launch wrapper (for VS Code extension)
#
# Why this exists:
# - In some remote/sandboxed environments, /root (and therefore ~/.claude.json)
#   can be on a read-only filesystem (EROFS), causing Claude Code to crash.
#
# What we do:
# - Force Claude Code to store ALL config/state under the project (writable).
# - Use a writable temp directory.

PROJECT_ROOT="/home/user/htdocs/srv1113343.hstgr.cloud"
CLAUDE_STATE_DIR="$PROJECT_ROOT/var/.claude-state"
CLAUDE_TMP_DIR="$PROJECT_ROOT/var/.claude-tmp"

mkdir -p "$CLAUDE_STATE_DIR" "$CLAUDE_TMP_DIR"

# Official env vars (see Claude Code docs)
export CLAUDE_CONFIG_DIR="$CLAUDE_STATE_DIR"
export CLAUDE_CODE_TMPDIR="$CLAUDE_TMP_DIR"

# Defensive: some dependencies may still write under $HOME
export HOME="$CLAUDE_STATE_DIR"

# Support both wrapper invocation styles:
# 1) Extension replaces the Claude binary with this wrapper:
#      wrapper --json --ide ...
# 2) Extension calls wrapper with the Claude binary as first argument:
#      wrapper /usr/bin/claude --json --ide ...
CLAUDE_BIN="/usr/bin/claude"
if [ $# -ge 1 ] && [[ "${1:-}" != -* ]]; then
	if [ -x "$1" ]; then
		CLAUDE_BIN="$1"
		shift
	else
		RESOLVED_BIN="$(command -v "$1" 2>/dev/null || true)"
		if [ -n "$RESOLVED_BIN" ] && [ -x "$RESOLVED_BIN" ]; then
			CLAUDE_BIN="$RESOLVED_BIN"
			shift
		fi
	fi
fi

exec "$CLAUDE_BIN" "$@"

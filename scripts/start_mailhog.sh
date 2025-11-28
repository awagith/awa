#!/usr/bin/env bash
set -euo pipefail
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAILHOG_BIN="$PROJECT_ROOT/bin/mailhog"
LOG_DIR="$PROJECT_ROOT/var/log/mailhog"
PID_FILE="$LOG_DIR/mailhog.pid"
SMTP_ADDR="127.0.0.1:1025"
UI_ADDR="127.0.0.1:8025"

if [[ ! -x "$MAILHOG_BIN" ]]; then
  echo "MailHog binary not found at $MAILHOG_BIN" >&2
  exit 1
fi

mkdir -p "$LOG_DIR"

if [[ -f "$PID_FILE" ]]; then
  if ps -p "$(cat "$PID_FILE")" >/dev/null 2>&1; then
    echo "MailHog already running with PID $(cat "$PID_FILE")"
    exit 0
  fi
fi

nohup "$MAILHOG_BIN" \
  -smtp-bind-addr "$SMTP_ADDR" \
  -ui-bind-addr "$UI_ADDR" \
  -api-bind-addr "$UI_ADDR" \
  > "$LOG_DIR/mailhog.log" 2>&1 &

PID=$!
echo $PID > "$PID_FILE"
echo "MailHog iniciado na porta SMTP $SMTP_ADDR e UI $UI_ADDR (PID $PID)."
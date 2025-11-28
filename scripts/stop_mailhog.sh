#!/usr/bin/env bash
set -euo pipefail
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/var/log/mailhog"
PID_FILE="$LOG_DIR/mailhog.pid"

if [[ ! -f "$PID_FILE" ]]; then
  echo "MailHog PID file not found. Nothing to stop."
  exit 0
fi

PID=$(cat "$PID_FILE")
if ps -p "$PID" >/dev/null 2>&1; then
  kill "$PID"
  echo "MailHog process $PID finalizado."
else
  echo "Processo $PID não está em execução."
fi

rm -f "$PID_FILE"
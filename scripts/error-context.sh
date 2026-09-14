#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

log_path="${1:-}"
if [[ -z "$log_path" ]]; then
    log_path="$(ls -1t storage/logs/laravel*.log storage/logs/server-error.log 2>/dev/null | head -n 1 || true)"
fi

if [[ -z "$log_path" || ! -f "$log_path" ]]; then
    echo "error: no se encontró un log; pasa la ruta como argumento" >&2
    exit 2
fi

match="$(rg -n -m 1 '(^|[. ])(ERROR|CRITICAL|ALERT|EMERGENCY)(:|\b)|Exception|Fatal error' "$log_path" || true)"
echo "log: $log_path"

if [[ -z "$match" ]]; then
    echo "sin errores reconocibles; últimas 20 líneas:"
    tail -n 20 "$log_path"
    exit 0
fi

line="${match%%:*}"
start=$((line > 3 ? line - 3 : 1))
end=$((line + 14))
echo "contexto: líneas $start-$end (primer error detectado)"
sed -n "${start},${end}p" "$log_path"

#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

failed=0
check_env() {
    local key="$1" expected="$2" actual
    actual="$(docker compose exec -T laravel.test php artisan tinker --execute="echo (string) config('$key');" 2>/dev/null)"
    if [[ "$actual" == "$expected" ]]; then
        echo "OK $key=$expected"
    else
        echo "ERROR $key esperaba '$expected' y obtuvo '$actual'" >&2
        failed=1
    fi
}

check_env app.env production
check_env app.debug ""
check_env app.timezone America/Managua
check_env app.locale es

if docker compose exec -T laravel.test php artisan migrate:status | grep -q 'Pending'; then
    echo "ERROR hay migraciones pendientes" >&2
    failed=1
else
    echo "OK migraciones al día"
fi

if curl -fsS http://localhost:8080/up | grep -q 'Application up'; then
    echo "OK endpoint de salud"
else
    echo "ERROR endpoint de salud" >&2
    failed=1
fi

exit "$failed"

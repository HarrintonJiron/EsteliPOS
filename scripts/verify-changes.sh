#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

run_all=0
if [[ "${1:-}" == "--all" ]]; then
    run_all=1
    shift
fi

tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/estelipos-verify.XXXXXX")"
trap 'rm -rf "$tmp_dir"' EXIT
changed_file="$tmp_dir/changed.txt"
tests_file="$tmp_dir/tests.txt"

if [[ "$#" -gt 0 ]]; then
    printf '%s\n' "$@" | sed '/^[[:space:]]*$/d' | sort -u > "$changed_file"
    git diff --check -- "$@"
else
    {
        git diff --name-only --diff-filter=ACMR HEAD
        git ls-files --others --exclude-standard
    } | sed '/^[[:space:]]*$/d' | sort -u > "$changed_file"
    git diff --check
fi

changed_count="$(wc -l < "$changed_file" | tr -d ' ')"
echo "verify: $changed_count archivo(s)"

php_files=()
shell_files=()
powershell_files=()
frontend_changed=0
product_php_changed=0

while IFS= read -r path; do
    [[ -f "$path" ]] || continue
    case "$path" in
        *.php) php_files+=("$path") ;;
        *.sh) shell_files+=("$path") ;;
        *.ps1) powershell_files+=("$path") ;;
    esac
    case "$path" in
        resources/css/*|resources/js/*|resources/views/*|package.json|package-lock.json|vite.config.*)
            frontend_changed=1
            ;;
    esac
    case "$path" in
        app/*.php|app/**/*.php|routes/*.php|bootstrap/*.php|config/*.php|database/*.php|database/**/*.php)
            product_php_changed=1
            ;;
    esac
done < "$changed_file"

if [[ "${#shell_files[@]}" -gt 0 ]]; then
    for path in "${shell_files[@]}"; do
        bash -n "$path"
    done
    echo "ok: bash -n (${#shell_files[@]})"
fi

if [[ "${#powershell_files[@]}" -gt 0 ]]; then
    if command -v pwsh >/dev/null 2>&1; then
        for path in "${powershell_files[@]}"; do
            pwsh -NoProfile -Command "[scriptblock]::Create((Get-Content -Raw -LiteralPath '$path')) | Out-Null"
        done
        echo "ok: PowerShell parse (${#powershell_files[@]})"
    else
        echo "skip: pwsh no disponible (${#powershell_files[@]} archivo(s))"
    fi
fi

if [[ "${#php_files[@]}" -gt 0 ]]; then
    for path in "${php_files[@]}"; do
        php -l "$path" >/dev/null
    done
    echo "ok: php -l (${#php_files[@]})"

    if [[ ! -x vendor/bin/pint ]]; then
        echo "error: falta vendor/bin/pint; instala dependencias fuera de este script" >&2
        exit 2
    fi
    vendor/bin/pint --test --quiet "${php_files[@]}"
    echo "ok: pint (${#php_files[@]})"
fi

add_test() {
    local path="$1"
    [[ -f "$path" ]] && printf '%s\n' "$path" >> "$tests_file"
}

: > "$tests_file"
while IFS= read -r path; do
    [[ -f "$path" ]] || continue
    case "$path" in
        tests/Feature/*.php|tests/Unit/*.php) add_test "$path" ;;
        deployment/*|deployment/**/*) add_test tests/Feature/DeploymentArtifactsTest.php ;;
        routes/*|bootstrap/*|app/Http/Middleware/*|app/Services/ModuleAccessService.php)
            add_test tests/Feature/LimitedUserNavigationTest.php
            add_test tests/Feature/ProductionRouteCoverageTest.php
            ;;
        *Facturacion*|*Sale*|resources/views/facturacion/*)
            add_test tests/Feature/ProductionSalesIntegrityTest.php
            add_test tests/Feature/SaleReceiptDiscountTest.php
            ;;
        *Compra*|*Purchase*)
            add_test tests/Feature/PurchaseCurrencyAndUnitsTest.php
            add_test tests/Feature/FinancialIntegrityRegressionTest.php
            ;;
        *Reparacion*|*Repair*) add_test tests/Feature/RepairOrderLockTest.php ;;
        *Credit*) add_test tests/Feature/FinancialIntegrityRegressionTest.php ;;
        *Inventario*|*Inventory*|*Product*|*Unit*|*Warehouse*)
            add_test tests/Feature/InventoryCatalogTest.php
            add_test tests/Feature/UnitManagementTest.php
            ;;
        *Setting*|*Auth*|*Password*|*User*|*Role*|*Permission*)
            add_test tests/Feature/SettingsStabilizationTest.php
            add_test tests/Feature/UserManagementTest.php
            add_test tests/Feature/RolePermissionManagementTest.php
            ;;
        *Accounting*|*Journal*|*Period*)
            add_test tests/Feature/FinancialIntegrityRegressionTest.php
            add_test tests/Feature/PeriodClosingTest.php
            ;;
        *Caja*|*Arqueo*)
            add_test tests/Feature/CashRegisterOpeningTest.php
            add_test tests/Feature/FinancialIntegrityRegressionTest.php
            ;;
    esac
done < "$changed_file"

sort -u "$tests_file" -o "$tests_file"
tests=()
while IFS= read -r path; do
    [[ -n "$path" ]] && tests+=("$path")
done < "$tests_file"

if [[ "$run_all" -eq 1 ]]; then
    php artisan test
elif [[ "${#tests[@]}" -gt 0 ]]; then
    echo "tests: ${#tests[@]} archivo(s) relacionado(s)"
    php artisan test "${tests[@]}"
elif [[ "$product_php_changed" -eq 1 ]]; then
    echo "tests: fallback ProductionSmokeTest"
    php artisan test tests/Feature/ProductionSmokeTest.php
else
    echo "skip: sin pruebas de producto inferidas"
fi

if [[ "$frontend_changed" -eq 1 ]]; then
    if [[ ! -d node_modules ]]; then
        echo "error: falta node_modules; instala dependencias fuera de este script" >&2
        exit 2
    fi
    npm run build >/dev/null
    echo "ok: npm run build"
fi

echo "verify: OK"

#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

echo "[1/6] Certificación interna"
docker compose exec -T laravel.test php artisan app:production-certify

echo "[2/6] Pruebas críticas"
php artisan test \
    tests/Feature/ProductionSalesIntegrityTest.php \
    tests/Feature/CreditOverrideTest.php \
    tests/Feature/CashRegisterOpeningTest.php \
    tests/Feature/FinancialIntegrityRegressionTest.php \
    tests/Feature/PurchaseProformaQualityAuditTest.php \
    tests/Feature/DataIntegrityCommandTest.php \
    tests/Feature/WarehouseTransferTest.php \
    tests/Feature/SystemResetServiceTest.php \
    tests/Feature/SaleReceiptDiscountTest.php \
    tests/Feature/DeploymentArtifactsTest.php

echo "[3/6] Suite completa"
composer test

echo "[4/6] Compilación frontend"
npm run build

echo "[5/6] Dependencias"
composer audit --locked
npm audit --omit=dev

echo "[6/6] Limpieza del parche"
git diff --check

echo "[APROBADO] Certificación exhaustiva completada."

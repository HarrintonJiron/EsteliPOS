# EsteliPOS repository guide

## Mapa del proyecto

- `app/Http/Controllers`, `app/Http/Requests`: flujos HTTP y validación.
- `app/Models`, `app/Services`: dominio, inventario, crédito y contabilidad.
- `routes/web.php`: rutas web y permisos por acción.
- `resources/views`: Blade; tickets térmicos en `facturacion/receipt.blade.php`, `reparaciones/ticket.blade.php` y `proformas/ticket.blade.php`.
- `database/migrations`, `database/seeders`: esquema y catálogos. Las pruebas usan SQLite en memoria (`phpunit.xml`); desarrollo Docker usa MySQL 8.4.
- `tests/Feature`, `tests/Unit`: Pest 4.
- `deployment/windows`: instalación, actualización, respaldo y diagnóstico en Windows.
- `deployment/build-release.sh`: genera `deployment/parche1.0.zip`.
- `deployment/build-ticket-patch.sh`: genera el parche aislado `deployment/parcheticket.zip` sin tocar datos.
- `scripts`: verificaciones locales concisas. Flujo de release: [`.agents/skills/estelipos-windows-release/SKILL.md`](.agents/skills/estelipos-windows-release/SKILL.md).

## Comandos reales

```bash
# instalación nueva; crea .env, clave, migra y compila
composer run setup

# desarrollo local o Docker/Sail
composer run dev
docker compose up -d
npm run dev

# formato, sintaxis/tipos disponibles y verificación selectiva
vendor/bin/pint --dirty
./scripts/verify-changes.sh [rutas...]

# pruebas: archivo relacionado primero; suite completa solo cuando el riesgo lo justifique
php artisan test tests/Feature/NombreTest.php
php artisan test --filter='nombre de prueba'
composer test

# frontend y revisión de un paquete ya construido
npm run build
./scripts/check-release.sh [deployment/parche1.0.zip]
./scripts/check-ticket-patch.sh [deployment/parcheticket.zip]
```

No hay PHPStan/Psalm, TypeScript ni ESLint configurados. `verify-changes.sh` usa `php -l`, Pint, pruebas Pest relacionadas y `npm run build` cuando corresponda; no instala dependencias.

## Convenciones y límites

- Sigue Pest, Form Requests y servicios existentes; no introduzcas otro patrón sin necesidad.
- Toda ruta autenticada que muta negocio requiere `permission:*`. Ocultar un botón no sustituye autorización del servidor.
- Ventas, compras, caja, crédito, inventario y asientos deben conservar transacciones, bloqueos e idempotencia. No aceptes precios/totales calculados por el navegador.
- Usa `NumberSequence` para documentos; no generes consecutivos con `max(id) + 1`.
- Los tickets son HTML/CSS de 80 mm y deben funcionar sin CDN ni Internet.
- No ejecutes migraciones/seeders sobre datos persistentes, builds de release, instalaciones ni descargas sin autorización.
- No modifiques `.env`, `database/database.sqlite`, `storage/app`, `backups/`, binarios de `deployment/windows/assets/` ni ZIP de entrega salvo que la tarea los incluya expresamente.
- Preserva el árbol Git sucio; no reviertas, stages, commits ni pushes ajenos.

## Terminado

- Diff limitado al encargo y sin espacios/errores de sintaxis.
- Formato aplicado o comprobado en archivos tocados.
- Pruebas relacionadas pasan; frontend modificado implica `npm run build`.
- Cambios de migración se prueban desde esquema limpio y sin tocar datos reales.
- Cambios Windows/release pasan `scripts/check-release.sh`; informa cualquier validación no disponible (por ejemplo, PowerShell en macOS).

## Eficiencia

- Razonamiento bajo para tareas pequeñas; medio por defecto; alto solo para depuración compleja, arquitectura o cambios amplios.
- Busca símbolos y fragmentos con `rg` antes de leer archivos completos.
- Limita logs al primer error útil y su contexto (`scripts/error-context.sh`).
- Ejecuta primero pruebas relacionadas y evita la suite completa si no aporta cobertura adicional.
- Mantén actualizaciones y respuestas finales concisas.

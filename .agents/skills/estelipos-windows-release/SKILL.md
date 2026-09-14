---
name: estelipos-windows-release
description: Verifica un parche Windows de EsteliPOS ya construido: estructura ZIP, checksum, versión, ausencia de datos, pruebas de despliegue y protecciones SQLite. Úsalo al revisar o entregar `deployment/parche*.zip` o `deployment/parcheticket.zip`; no lo uses para cambios funcionales ordinarios ni para construir/publicar sin solicitud explícita.
---

# Release Windows de EsteliPOS

1. Lee `VERSION` y confirma qué ZIP pidió revisar el usuario.
2. Para releases completos ejecuta `scripts/check-release.sh [ruta-del-zip]`. Para el parche aislado del ticket ejecuta `scripts/check-ticket-patch.sh [ruta-del-zip]`; no repitas manualmente sus comprobaciones.
3. Si falla, corrige únicamente scripts, empaquetado o archivos incluidos relacionados con el fallo y vuelve a ejecutarlo.
4. Ejecuta `deployment/build-release.sh --allow-dirty VERSION` solo para releases completos. Para el ticket aislado usa `deployment/build-ticket-patch.sh`; no instala dependencias ni toca la base.
5. No abras, reemplaces ni empaquetes `.env`, `database.sqlite`, `storage/app` o `backups`.
6. Informa versión, SHA-256, pruebas y cualquier validación Windows no ejecutable desde el host actual.

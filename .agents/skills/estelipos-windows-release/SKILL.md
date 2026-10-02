---
name: estelipos-windows-release
description: Verifica el instalador Windows oficial de EsteliPOS basado en Apache, PHP y MySQL: checksum, versión, ausencia de datos y pruebas de despliegue. Úsalo al revisar o entregar `EsteliPOS-Setup-*.exe`; no lo uses para cambios funcionales ordinarios ni para construir/publicar sin solicitud explícita.
---

# Release Windows de EsteliPOS

1. Lee `VERSION` y confirma qué instalador pidió revisar el usuario.
2. Ejecuta `scripts/check-release.sh [ruta-del-exe]`; no repitas manualmente sus comprobaciones.
3. Si falla, corrige únicamente scripts, empaquetado o archivos incluidos relacionados con el fallo y vuelve a ejecutarlo.
4. Construye únicamente en Windows con `deployment/installer/scripts/Build-EsteliPOSInstaller.ps1`; requiere los binarios declarados en `deployment/installer/README.md` y NSIS.
5. No abras, reemplaces ni empaquetes `.env`, `database.sqlite`, `storage/app` o `backups`.
6. Informa versión, SHA-256, pruebas y cualquier validación Windows no ejecutable desde el host actual.

# Instalador oficial de EsteliPOS

Esta es la única línea de instalación admitida para producción. Instala servicios locales independientes de Apache y MySQL, PHP Thread Safe y la aplicación EsteliPOS.

## Confiabilidad operativa

- Detecta una instalación anterior desde `C:\ProgramData\EsteliPOS\installation.json`; el mismo EXE funciona como instalador y actualizador.
- Verifica espacio, checksums, ejecutables, extensiones PHP, servicios, puertos, conexión real con MySQL, migraciones y respuesta HTTP antes de finalizar.
- Si un puerto guardado está ocupado por otro programa, selecciona uno libre y actualiza `.env`, Apache, MySQL y el acceso directo.
- Antes de actualizar conserva aplicación, archivos y un volcado lógico de MySQL. Si una migración falla, intenta restaurar automáticamente la base y la versión anterior.
- Registra una tarea diaria a las 19:00. Los respaldos comprimidos se guardan en `C:\ProgramData\EsteliPOS\backups\automatic` y se conservan 30 días.
- El desinstalador elimina servicios y la tarea programada, pero conserva datos, respaldos y registros en `ProgramData`.

Para soporte, ejecuta como administrador `installer\Diagnose-EsteliPOS.ps1` desde la carpeta instalada. El reporte queda en `C:\ProgramData\EsteliPOS\diagnostics`.

## Componentes requeridos

Antes de construir, coloca en `deployment/installer/payload/`:

- `apache-win64.zip`
- `php-8.5.10-Win32-vs17-x64.zip`
- `mysql-8.0.21-winx64.zip`
- `vc_redist.x64.exe`

Los archivos no se guardan en Git. El proceso genera un manifiesto con tamaño y SHA-256 de cada componente.

## Construcción en Windows

Ejecuta PowerShell como administrador:

```powershell
.\deployment\installer\scripts\Build-EsteliPOSInstaller.ps1
```

El resultado esperado es `deployment/installer/dist/EsteliPOS-Setup-VERSION.exe` junto con su checksum SHA-256.

## Verificación

Desde el repositorio:

```bash
./scripts/check-release.sh deployment/installer/dist/EsteliPOS-Setup-VERSION.exe
```

El paquete no debe contener `.env`, bases de datos, archivos subidos, respaldos ni datos de clientes.

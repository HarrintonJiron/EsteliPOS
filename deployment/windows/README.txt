ESTELIPOS - INSTALACION LOCAL PARA WINDOWS
Northlink Microsystem
Version: 2.0.0-final

REQUISITOS
- Windows 10 u 11 de 64 bits (Pro, Enterprise o Education para perfil IIS).
- PHP 8.4.1 o superior (Thread Safe para IIS). El instalador puede instalarlo solo.
- IIS: el instalador lo activa automaticamente en la opcion 1 (no hace falta instalarlo antes).
- Extensiones PHP: ctype, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite,
  sqlite3, tokenizer, xml y zip.
- El paquete ZIP oficial generado desde deployment/build-release.sh.
- Microsoft Edge o Google Chrome.
- PC servidor y tablets/celulares en la misma red privada (no red de invitados).

PAQUETE PARA ENVIAR AL TECNICO
- deployment\produccion2.0.zip  (contiene EsteliPOSProduccion2.0.zip + checksum SHA256)
1. Extraiga deployment\produccion2.0.zip (o el archivo produccion2.0.zip recibido)
2. Verifique SHA256 con EsteliPOSProduccion2.0.zip.sha256 si lo desea
3. Extraiga EsteliPOSProduccion2.0.zip en una ruta permanente, por ejemplo:
   C:\Northlink\EsteliPOS
4. Doble clic en Instalar-EsteliPOS.bat (en la raiz del paquete extraido)
   O clic derecho -> Ejecutar como administrador
   El instalador muestra un menu:
   - Opcion 1: IIS (instala IIS, PHP TS, URL Rewrite y sitio en puerto 8080)
   - Opcion 2: Simple (1 caja, sin IIS)
   - Opcion 3: Solo verificar PHP sin instalar
5. Si algo falla, la ventana indica la FASE, CODIGO DE ERROR y
   POSIBLES SOLUCIONES paso a paso para el tecnico.
6. Revise tambien: storage\logs\install-*.log
7. Indique correo y contrasena del administrador cuando se solicite.
8. Al finalizar se abrira EsteliPOS y la hoja de acceso en red.

ATAJOS
- Instalar-EsteliPOS.bat              -> instalador con menu (raiz del paquete extraido)
- deployment\windows\Install-EsteliPOS.bat -> mismo instalador

CODIGOS DE ERROR COMUNES (instalacion)
  1  = ejecutar como administrador
  2  = verificacion previa (PHP/paquete)
  3  = PHP no en PATH
  5  = extensiones PHP faltantes en php.ini
  6  = falta php-cgi.exe (use PHP Thread Safe)
  15 = sitio IIS, FastCGI o URL Rewrite
  20 = instalacion automatica de IIS fallo
  19 = instalacion automatica de PHP fallo

INSTALACION MANUAL (solo si el .bat falla)
powershell -ExecutionPolicy Bypass -File deployment\windows\Deploy-EsteliPOS.ps1 -ServerProfile IIS -Port 8080

ACCESO DESDE OTROS DISPOSITIVOS
- Todos deben usar la misma Wi-Fi de la ferreteria.
- Abra la hoja "EsteliPOS - Acceso en red" del escritorio o escanee el QR.
- Ejemplo: http://192.168.1.50:8080
- Para que la IP no cambie, reserve en el router la IP mostrada para la MAC
  del equipo servidor (la hoja de acceso incluye ambos datos).

ARRANQUE AUTOMATICO
- Perfil IIS: el servicio W3SVC de Windows mantiene el sitio activo.
- Perfil Simple: tarea "EsteliPOS - Servidor" con php artisan serve.
- En ambos perfiles hay tarea de respaldo diario a las 7:00 PM.
- Use Start-EsteliPOS.ps1 / Stop-EsteliPOS.ps1 para control manual.

IMPRESION Y CAJA
- La impresion termica y la gaveta se configuran en la PC de caja.
- Las tablets pueden vender o consultar, pero la impresion silenciosa funciona
  mejor en la PC con impresora predeterminada configurada.
- Abra siempre EsteliPOS desde el acceso directo del escritorio.

RESPALDOS
- Diarios a las 7:00 PM en storage\app\backups.
- Durante la instalacion puede indicar una segunda copia en USB o red.
- Backup-EsteliPOS.ps1 crea una copia inmediata.

HERRAMIENTAS
- Instalar-EsteliPOS.bat           -> instalador con menu (raiz del paquete)
- Install-EsteliPOS.bat            -> instalador con menu y mensajes de error
- Show-InstallError.ps1            -> muestra soluciones por codigo de error
- EsteliPOS-PHP.ps1               -> descarga/instala PHP TS en C:\EsteliPOS\PHP
- Verify-PHP-EsteliPOS.ps1        -> comprobar PHP antes de instalar
- Deploy-EsteliPOS.ps1            -> instalacion completa con parametros
- Test-EsteliPOSInstallation.ps1  -> pruebas automaticas post-instalacion
- Update-EsteliPOS.ps1            -> actualizar version con respaldo
- Start-EsteliPOS.ps1             -> iniciar servidor
- Stop-EsteliPOS.ps1              -> detener servidor
- Show-NetworkAccess.ps1          -> abrir hoja URL + QR
- Diagnose-EsteliPOS.ps1          -> estado, IP, MAC, IIS y pruebas LAN
- Backup-EsteliPOS.ps1            -> respaldo manual

DOCUMENTACION
- informes\GUIA_INSTALACION_PRODUCCION_WINDOWS.md
- informes\REPORTE_PUESTA_EN_PRODUCCION.md

PERFILES DE SERVIDOR
- IIS (predeterminado): el instalador activa IIS, instala URL Rewrite y configura PHP FastCGI.
  Atiende varias peticiones en paralelo. Recomendado para 2+ dispositivos.
  Requiere Windows Pro, Enterprise o Education (no Windows Home).
- Simple: php artisan serve. Mas facil, ideal para 1 caja con poca carga.

LIMITACIONES
- SQLite sigue limitando escrituras simultaneas aunque use IIS.
- Si necesita 3+ cajas vendiendo a la vez con alto volumen, planifique MySQL.

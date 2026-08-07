ESTELIPOS - INSTALACION LOCAL PARA WINDOWS
Northlink Microsystem

REQUISITOS
- Windows 10 u 11 de 64 bits.
- PHP 8.4.1 o superior instalado manualmente y agregado al PATH.
- Extensiones PHP: ctype, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite,
  sqlite3, tokenizer, xml y zip.
- El paquete ZIP oficial generado desde deployment/build-release.sh.
- Microsoft Edge o Google Chrome.
- PC servidor y tablets/celulares en la misma red privada (no red de invitados).

INSTALACION RAPIDA
1. Extraiga el ZIP en una ruta permanente, por ejemplo:
   C:\Northlink\EsteliPOS
2. Instale PHP 8.4+ y agreguelo al PATH del sistema.
3. Clic derecho en deployment\windows\Install-EsteliPOS.bat
   -> Ejecutar como administrador
   (o abra PowerShell como administrador y ejecute Deploy-EsteliPOS.ps1)
4. Indique correo y contrasena del administrador.
5. Al finalizar se abrira:
   - EsteliPOS en la PC de caja
   - Una hoja con URL, QR e instrucciones para tablets

ACCESO DESDE OTROS DISPOSITIVOS
- Todos deben usar la misma Wi-Fi de la ferreteria.
- Abra la hoja "EsteliPOS - Acceso en red" del escritorio o escanee el QR.
- Ejemplo: http://192.168.1.50:8080
- Para que la IP no cambie, reserve en el router la IP mostrada para la MAC
  del equipo servidor (la hoja de acceso incluye ambos datos).

ARRANQUE AUTOMATICO
- Se registra la tarea "EsteliPOS - Servidor" al iniciar sesion y al encender
  Windows (con reinicio automatico si el proceso falla).
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
- Install-EsteliPOS.bat       -> instalador con doble clic (como admin)
- Deploy-EsteliPOS.ps1        -> instalacion completa
- Start-EsteliPOS.ps1         -> iniciar servidor
- Stop-EsteliPOS.ps1          -> detener servidor
- Show-NetworkAccess.ps1      -> abrir hoja URL + QR
- Diagnose-EsteliPOS.ps1      -> estado, IP, MAC y pruebas LAN
- Backup-EsteliPOS.ps1        -> respaldo manual

LIMITACION ACTUAL
Perfil pensado para 1 PC servidor + varios dispositivos en LAN con carga
moderada (SQLite + php artisan serve). Si necesita varias cajas vendiendo al
mismo tiempo con alto volumen, planifique MySQL y un servidor web dedicado.

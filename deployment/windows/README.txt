ESTELIPOS - INSTALACION LOCAL PARA WINDOWS
Northlink Microsystem
Version: 1.0.0-final

REQUISITOS
- Windows 10 u 11 de 64 bits (Pro, Enterprise o Education para perfil IIS).
- PHP 8.4.1 o superior (Thread Safe para IIS). PHP 8.4.24 x64 viene incluido.
- IIS: el instalador lo activa automaticamente en la opcion 1 (no hace falta instalarlo antes).
- Extensiones PHP: ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite,
  sqlite3, tokenizer, xml y zip.
- El paquete ZIP oficial generado desde deployment/build-release.sh.
- IIS URL Rewrite 2.1 x64 y Visual C++ Redistributable vienen incluidos.
- La instalacion de PHP, Visual C++ y URL Rewrite funciona sin internet.
- Microsoft Edge o Google Chrome.
- PC servidor y tablets/celulares en la misma red privada (no red de invitados).

PAQUETE PARA ENVIAR AL TECNICO
- deployment\parche1.0.zip
  (contiene INSTALAR.bat + EsteliPOSProduccion1.0.zip + checksum SHA256)

FORMA MAS FACIL (RECOMENDADA)
1. Extraiga parche1.0.zip en cualquier carpeta (USB o Escritorio)
2. Doble clic en INSTALAR.bat  (acepte UAC / administrador)
3. Elija:
   - Instalar IIS (recomendado) si es PC nueva
   - Actualizar si ya hay datos en C:\Northlink\EsteliPOS
4. Espere "LISTO". Se crea C:\Northlink\EsteliPOS y accesos en el escritorio.
5. Si falla: lea el codigo de error en pantalla y storage\logs\install-*.log

FORMA MANUAL (alternativa)
1. Extraiga EsteliPOSProduccion1.0.zip en C:\Northlink\EsteliPOS
2. Ejecute Instalar-EsteliPOS.bat como administrador
   - Opcion 1: IIS (instala IIS, PHP TS, URL Rewrite, puerto 8080)
   - Opcion 2: Simple (1 caja)
   - Opcion 3: Solo verificar PHP
3. Indique correo/contrasena de administrador cuando se solicite.

ACTUALIZAR SIN PERDER DATOS (PC YA INSTALADA)
1. Copie deployment\parche1.0.zip a la PC del cliente (USB o red),
   por ejemplo: C:\Northlink\parche1.0.zip
2. RECOMENDADO: pase la ruta completa del ZIP NUEVO (evita usar un ZIP viejo):
   C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat C:\Northlink\parche1.0.zip
   Si lo ejecuta sin ruta, pedira confirmar el ZIP mas reciente detectado.
3. El actualizador:
   - Muestra version actual y version del paquete
   - Crea respaldo en backups\YYYYMMDD_HHMMSS
   - Conserva .env, database.sqlite y storage\app
   - REEMPLAZA codigo/vistas/vendor/assets (no mezcla carpetas viejas)
   - Ejecuta php artisan migrate --force (no borra ventas/inventario)
   - Limpia cache y reinicia IIS/app pool
   - Si falla, restaura automaticamente la version anterior
4. Al terminar debe mostrar: Version nueva: 1.0.0-final
5. En el navegador use Ctrl+F5 (o ventana privada) si aun ve la pantalla vieja.
6. No use Instalar-EsteliPOS.bat sobre una instalacion con datos si solo quiere actualizar.
7. Borre ZIPs viejos (produccion2.0.zip, etc.) de C:\Northlink para no confundirse.

ATAJOS
- Instalar-EsteliPOS.bat              -> instalador NUEVO con menu (raiz del paquete)
- Actualizar-EsteliPOS.bat            -> actualiza instalacion existente sin perder datos
- deployment\windows\Install-EsteliPOS.bat -> mismo instalador
- deployment\windows\Actualizar-EsteliPOS.bat -> mismo actualizador

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
- Instalar-EsteliPOS.bat           -> instalacion NUEVA con menu (raiz del paquete)
- Actualizar-EsteliPOS.bat         -> actualizacion sin perder datos (raiz del paquete)
- Install-EsteliPOS.bat            -> instalador con menu y mensajes de error
- Show-InstallError.ps1            -> muestra soluciones por codigo de error
- EsteliPOS-PHP.ps1               -> descarga/instala PHP TS en C:\EsteliPOS\PHP
- Verify-PHP-EsteliPOS.ps1        -> comprobar PHP antes de instalar
- Deploy-EsteliPOS.ps1            -> instalacion completa con parametros
- Test-EsteliPOSInstallation.ps1  -> pruebas automaticas post-instalacion
- Update-EsteliPOS.ps1            -> motor de actualizacion con respaldo y rollback
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

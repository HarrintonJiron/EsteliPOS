ESTELIPOS - INSTALACION LOCAL PARA WINDOWS
Northlink Microsystem
Version: 1.0.3

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

PAQUETE PARA ENVIAR AL TECNICO (SISTEMA COMPLETO)
- deployment\produccion1.0.zip
  (contiene INSTALAR.bat + EsteliPOSProduccion1.0.zip + checksum SHA256)
  Actualiza TODO el sistema: app, vistas, migraciones, assets, instalador IIS.

FORMA MAS FACIL (RECOMENDADA)
1. Extraiga produccion1.0.zip en cualquier carpeta (USB o Escritorio)
2. Doble clic en INSTALAR.bat  (acepte UAC / administrador)
3. Se abre el asistente grafico:
   - Instalar nuevo o actualizar sin perder datos
   - IIS (recomendado) o Simple
   - Usuario administrador
   - Red LAN: IP, MAC, puerto 8080 y opcion de fijar la IP en esta PC
4. Espere "LISTO". Se crea C:\Northlink\EsteliPOS y accesos en el escritorio.
   El instalador NO abre el navegador al final (eso congelaba algunas PCs).
   Abra el acceso directo del escritorio cuando termine.
   Por defecto carga datos de demostracion (productos, clientes y ventas).
5. Si falla: pulse Copiar informe (o Copiar log) y pegue el bloque en un correo.
   Tambien esta en storage\logs\install-*.log
   Para consola (tecnicos): Instalar-EsteliPOS.bat IIS   o   Instalar-EsteliPOS.bat Simple

FORMA MANUAL (alternativa)
1. Extraiga EsteliPOSProduccion1.0.zip en C:\Northlink\EsteliPOS
2. Ejecute Instalar-EsteliPOS.bat (asistente grafico) o Instalar-EsteliPOS-Grafico.bat
   Consola: Instalar-EsteliPOS.bat IIS | Simple | Verify
3. En el paso de red LAN puede fijar la IP de esta PC. Tambien reserve esa IP
   en el router usando la MAC mostrada (el instalador no puede configurar el router).

ACTUALIZAR SIN PERDER DATOS (PC YA INSTALADA)
1. Copie deployment\produccion1.0.zip a la PC del cliente (USB o red),
   por ejemplo: C:\Northlink\produccion1.0.zip
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
- Instalar-EsteliPOS.bat              -> asistente grafico (IIS|Simple = consola)
- Instalar-EsteliPOS-Grafico.bat      -> mismo asistente grafico
- Actualizar-EsteliPOS.bat            -> actualiza instalacion existente sin perder datos
- deployment\windows\Install-EsteliPOS.bat -> mismo instalador
- deployment\windows\Install-EsteliPOS-GUI.ps1 -> motor WinForms del asistente
- deployment\windows\Actualizar-EsteliPOS.bat -> mismo actualizador

CODIGOS DE ERROR COMUNES (instalacion)
  1  = ejecutar como administrador
  2  = verificacion previa (PHP/paquete)
  3  = PHP no en PATH
  5  = extensiones PHP faltantes en php.ini
  6  = falta php-cgi.exe (use PHP Thread Safe)
  15 = sitio IIS, FastCGI o URL Rewrite
  20 = instalacion automatica de IIS fallo
  21 = no se pudo fijar la IP en esta PC
  22 = Windows sigue identificando la red (aviso; el paquete nuevo no aborta)
  19 = instalacion automatica de PHP fallo

INSTALACION MANUAL (solo si el .bat falla)
powershell -ExecutionPolicy Bypass -File deployment\windows\Deploy-EsteliPOS.ps1 -ServerProfile IIS -Port 8080

ACCESO DESDE OTROS DISPOSITIVOS
- Todos deben usar la misma Wi-Fi de la ferreteria.
- Abra la hoja "EsteliPOS - Acceso en red" del escritorio o escanee el QR.
- Ejemplo: http://192.168.1.50:8080
- Para que la IP no cambie, reserve en el router la IP mostrada para la MAC
  del equipo servidor (la hoja de acceso incluye ambos datos).
- El asistente grafico puede fijar esa IP en Windows (DHCP desactivado en esta PC).
  Eso no sustituye la reserva en el router, pero evita que Windows pida otra IP.
  Si Windows muestra "Identifying...", el instalador espera y continua; no aborta.

ACTUALIZAR UNA INSTALACION ANTIGUA (sin perder datos)
- Copie produccion1.0.zip (o EsteliPOSProduccion1.0.zip) a la PC.
- En la carpeta instalada ejecute como Administrador:
    Actualizar-EsteliPOS.bat
  o:
    Actualizar-EsteliPOS.bat C:\ruta\produccion1.0.zip
- Conserva .env, database.sqlite y storage\app.
- Reemplaza app, resources, vendor, public/build, deployment\windows, etc.
- Reaplica arranque LAN (0.0.0.0), firewall, tarea programada y acceso directo.
- No use un parche viejo si tiene el ZIP completo de produccion.

ARRANQUE AUTOMATICO
- Perfil IIS: el servicio W3SVC de Windows mantiene el sitio activo.
- Perfil Simple: tarea "EsteliPOS - Servidor" ejecuta:
    php artisan serve --host=0.0.0.0 --port=8080
  (NUNCA php -S 127.0.0.1 solo; eso bloquea tablets/otras PCs de la red.)
- En ambos perfiles hay tarea de respaldo diario a las 7:00 PM.
- Acceso directo "EsteliPOS" = Abrir-EsteliPOS.bat / Launch-EsteliPOS.ps1
  (inicia el servidor, espera HTTP listo, luego abre el navegador con la URL LAN).
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
- Instalar-EsteliPOS.bat           -> asistente grafico (sin parametros)
- Instalar-EsteliPOS-Grafico.bat   -> mismo asistente grafico
- Actualizar-EsteliPOS.bat         -> actualizacion sin perder datos (raiz del paquete)
- Install-EsteliPOS.bat            -> instalador con menu y mensajes de error
- Install-EsteliPOS-GUI.ps1        -> asistente WinForms (LAN, IP fija, IIS/Simple)
- Show-InstallError.ps1            -> muestra soluciones por codigo de error
- EsteliPOS-PHP.ps1               -> descarga/instala PHP TS en C:\EsteliPOS\PHP
- Verify-PHP-EsteliPOS.ps1        -> comprobar PHP antes de instalar
- Deploy-EsteliPOS.ps1            -> instalacion completa con parametros
- Test-EsteliPOSInstallation.ps1  -> pruebas automaticas post-instalacion
- Update-EsteliPOS.ps1            -> motor de actualizacion con respaldo y rollback
- Start-EsteliPOS.ps1             -> iniciar servidor (Simple: --host=0.0.0.0)
- Launch-EsteliPOS.ps1            -> iniciar + esperar + abrir navegador
- Abrir-EsteliPOS.bat             -> acceso directo recomendado (raiz del proyecto)
- Reparar-EsteliPOS-LAN.bat       -> repara Simple si solo funciona localhost
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
- Simple: PHP embebido (php -S). Mas facil, obligatorio en Windows Home.

LIMITACIONES
- SQLite sigue limitando escrituras simultaneas aunque use IIS.
- Si necesita 3+ cajas vendiendo a la vez con alto volumen, planifique MySQL.

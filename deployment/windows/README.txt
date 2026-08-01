ESTELIPOS - DESPLIEGUE LOCAL PARA WINDOWS
Northlink Microsystem

REQUISITOS
- Windows 10 u 11 de 64 bits.
- PHP 8.4.1 o superior agregado al PATH.
- Extensiones PHP: ctype, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite,
  sqlite3, tokenizer, xml y zip.
- El paquete ZIP oficial generado desde la rama validada.
- Controlador de la impresora termica de 80 mm instalado en Windows.

INSTALACION
1. Extraiga el ZIP en una ruta permanente, por ejemplo:
   C:\Northlink\EsteliPOS
2. No mueva ni borre esa carpeta despues de instalar.
3. Abra PowerShell con la opcion Ejecutar como administrador.
4. Ejecute:
   Set-ExecutionPolicy -Scope Process Bypass
   .\deployment\windows\Deploy-EsteliPOS.ps1
5. Indique el correo y una contrasena segura para el administrador.
6. Espere el mensaje DESPLIEGUE COMPLETADO.
7. Use el acceso directo EsteliPOS creado en el escritorio.

RESPALDOS
- Se ejecutan diariamente a las 7:00 PM mientras el usuario este conectado.
- Se conservan durante 30 dias en storage\app\backups.
- Durante la instalacion puede configurar una segunda copia en USB, red o nube.
- No cargue datos reales sin verificar al menos una copia externa.
- Para probar una restauracion, conserve el archivo actual antes de reemplazar
  database\database.sqlite por una copia.

HERRAMIENTAS
- Start-EsteliPOS.ps1: inicia el sistema.
- Stop-EsteliPOS.ps1: detiene el sistema.
- Backup-EsteliPOS.ps1: crea un respaldo inmediato.
- Diagnose-EsteliPOS.ps1: muestra el estado y errores basicos.

LIMITACION
Este perfil es para una sola computadora y acceso local. Para varias cajas o
acceso por red se debe desplegar con MySQL y un servidor web dedicado.

ESTELIPOS - DESPLIEGUE LOCAL PARA WINDOWS
Northlink Microsystem

REQUISITOS
- Windows 10 u 11 de 64 bits.
- PHP 8.4.1 o superior agregado al PATH.
- Extensiones PHP: ctype, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite,
  sqlite3, tokenizer, xml y zip.
- El paquete ZIP oficial generado desde la rama validada.
- Controlador de la impresora termica de 80 mm instalado en Windows.
- Microsoft Edge o Google Chrome.
- PC y tablet conectadas a la misma red privada (no red de invitados).

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
8. Abra desde la tablet la direccion que muestra el instalador.
9. Reserve esa direccion IP para la PC en el router. Si la IP cambia, la tablet
   no encontrara el sistema hasta actualizar la direccion o repetir el despliegue.

IMPRESION AUTOMATICA Y GAVETA
- Establezca la impresora termica como impresora predeterminada de Windows.
- Configure papel de 80 mm y desactive encabezados y pies del navegador.
- En las propiedades del controlador configure la apertura de gaveta antes o
  despues de imprimir. La gaveta debe estar conectada al puerto de la impresora.
- Abra siempre el sistema desde el acceso directo EsteliPOS. Ese acceso inicia
  Edge/Chrome con impresion silenciosa.
- Al pulsar Imprimir Recibo Termico en la pantalla de cambio, el ticket se envia
  directamente a la impresora predeterminada y el controlador abre la gaveta.
- La tablet no imprime ni abre la gaveta; esas acciones quedan en la PC de caja.

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
Este perfil admite una PC de caja y una tablet en la misma red, con carga ligera.
La venta e impresion se realizan desde la PC. Para varias cajas vendiendo al
mismo tiempo se debe desplegar con MySQL y un servidor web dedicado.

# Catalogo de errores de instalacion EsteliPOS (codigos de salida + soluciones para tecnicos)

$Script:EsteliPOSInstallErrorCatalog = @{
    1  = @{
        Phase = "Permisos de administrador"
        Title = "El instalador debe ejecutarse como administrador"
        Solutions = @(
            "Cierre esta ventana y haga clic derecho en Instalar-EsteliPOS.bat -> Ejecutar como administrador."
            "Si Windows pide confirmacion UAC, pulse Si."
            "En equipos corporativos, pida al administrador de TI permisos de administrador local."
        )
    }
    2  = @{
        Phase = "Verificacion previa (PHP y paquete)"
        Title = "La comprobacion inicial no paso"
        Solutions = @(
            "Revise los mensajes en ROJO que aparecieron arriba (FALLO: ...)."
            "Ejecute manualmente: powershell -File deployment\windows\Verify-PHP-EsteliPOS.ps1 -ServerProfile IIS"
            "Corrija PHP, extensiones, php-cgi (Thread Safe) o el paquete ZIP antes de reinstalar."
        )
    }
    3  = @{
        Phase = "PHP no encontrado"
        Title = "PHP no esta instalado o no esta en el PATH del sistema"
        Solutions = @(
            "Descargue PHP 8.4+ Thread Safe (TS) x64 desde https://windows.php.net/download/"
            "Extraiga en C:\php (o similar) y agregue esa carpeta al PATH del sistema."
            "Abra una NUEVA ventana de CMD y ejecute: php -v"
            "Debe mostrar PHP 8.4.1 o superior."
        )
    }
    4  = @{
        Phase = "Version de PHP"
        Title = "La version de PHP es demasiado antigua"
        Solutions = @(
            "Instale PHP 8.4.1 o superior (Thread Safe para perfil IIS)."
            "Verifique con: php -v"
            "Si tiene varias versiones, ajuste el PATH para que apunte a la correcta."
        )
    }
    5  = @{
        Phase = "Extensiones PHP"
        Title = "Faltan extensiones obligatorias en php.ini"
        Solutions = @(
            "Abra php.ini (ubicacion: php --ini) y descomente o agregue las extensiones faltantes."
            "Requeridas: ctype, dom, fileinfo, gd, mbstring, openssl, pdo_sqlite, sqlite3, tokenizer, xml, zip."
            "Reinicie cualquier servicio IIS despues de cambiar php.ini."
        )
    }
    6  = @{
        Phase = "PHP para IIS (FastCGI)"
        Title = "No se encontro php-cgi.exe (PHP Non-Thread Safe o instalacion incompleta)"
        Solutions = @(
            "Instale la variante Thread Safe (TS) de PHP, NO la Non-Thread Safe (NTS)."
            "php-cgi.exe debe estar en la misma carpeta que php.exe."
            "Descargue de nuevo desde https://windows.php.net/download/ el ZIP VS16 x64 Thread Safe."
        )
    }
    7  = @{
        Phase = "Paquete incompleto (vendor)"
        Title = "Faltan dependencias PHP (carpeta vendor)"
        Solutions = @(
            "Use el ZIP oficial generado con deployment/build-release.sh, no una copia del codigo fuente."
            "Si construye el paquete: composer install --no-dev en la maquina de build."
            "No elimine la carpeta vendor del paquete extraido."
        )
    }
    8  = @{
        Phase = "Paquete incompleto (frontend)"
        Title = "Faltan recursos web compilados (public/build)"
        Solutions = @(
            "Use el ZIP de release que incluye npm run build."
            "En desarrollo: ejecute npm install && npm run build antes de empaquetar."
            "Debe existir public/build/manifest.json o public/css/app-ui.css."
        )
    }
    9  = @{
        Phase = "Paquete incompleto (IIS)"
        Title = "Falta public/web.config"
        Solutions = @(
            "Regenera el paquete desde la rama versionproduccion1.0 o posterior."
            "Copie public/web.config del repositorio si instala desde codigo fuente."
        )
    }
    10 = @{
        Phase = "Puerto de red"
        Title = "El puerto configurado no es valido"
        Solutions = @(
            "Use un puerto entre 1024 y 65535 (predeterminado: 8080)."
            "Evite puertos reservados por otros programas (80, 443, 3306)."
        )
    }
    11 = @{
        Phase = "Red local (LAN)"
        Title = "No se detecto una IP de red local"
        Solutions = @(
            "Conecte el PC al Wi-Fi o cable de la ferreteria (misma red que tablets)."
            "Verifique que la tarjeta de red tenga IPv4 asignada (ipconfig)."
            "Desactive VPN o adaptadores virtuales que oculten la red real."
            "Puede forzar IP con: Deploy-EsteliPOS.ps1 -LanAddress 192.168.x.x"
        )
    }
    12 = @{
        Phase = "Clave de aplicacion"
        Title = "No se pudo generar APP_KEY"
        Solutions = @(
            "Verifique permisos de escritura en la carpeta del proyecto y en .env."
            "Ejecute manualmente: php artisan key:generate --force"
            "Revise storage\logs\laravel.log por errores de permisos."
        )
    }
    13 = @{
        Phase = "Datos del administrador"
        Title = "No se indico el correo del administrador"
        Solutions = @(
            "Vuelva a ejecutar el instalador e ingrese un correo valido cuando se solicite."
            "O use: Deploy-EsteliPOS.ps1 -AdminEmail admin@suempresa.com"
        )
    }
    14 = @{
        Phase = "Base de datos e instalacion Laravel"
        Title = "app:install-production fallo"
        Solutions = @(
            "La contrasena debe tener al menos 12 caracteres."
            "Verifique que database/database.sqlite sea escribible."
            "Ejecute: php artisan app:install-production --admin-email=... --force"
            "Revise storage\logs\laravel.log y el log de instalacion en storage\logs\install-*.log"
        )
    }
    15 = @{
        Phase = "Configuracion IIS"
        Title = "No se pudo configurar el sitio IIS, FastCGI o URL Rewrite"
        Solutions = @(
            "IIS ya se instalo (codigo 20 es otra fase). El error 15 falla al crear el sitio o modulos."
            "Revise storage\logs\install-*.log: el mensaje dira 'URL Rewrite', 'FastCGI', 'Puerto' o 'Handler PHP'."
            "Causa mas comun: falta IIS URL Rewrite. Coloque urlrewrite2.exe en deployment\windows\assets\ y reinstale."
            "Sin internet: descargue urlrewrite2.exe en otro PC, copielo a assets\ y ejecute como administrador."
            "Despues de instalar URL Rewrite ejecute: iisreset /restart"
            "Si dice puerto ocupado: netstat -ano | findstr :8080 y cierre el proceso indicado."
            "Si Windows pidio reinicio tras instalar IIS, reinicie el PC antes de volver a instalar."
            "Ejecute: powershell -File deployment\windows\Diagnose-EsteliPOS.ps1"
        )
    }
    20 = @{
        Phase = "Instalacion automatica de IIS"
        Title = "No se pudo instalar IIS en Windows"
        Solutions = @(
            "IIS no esta disponible en Windows Home. Use opcion 2 (Simple) o actualice a Windows Pro."
            "Ejecute el instalador como administrador (clic derecho -> Ejecutar como administrador)."
            "Si Windows pidio reinicio despues de activar IIS, reinicie y vuelva a ejecutar Instalar-EsteliPOS.bat."
            "Active IIS manualmente: Panel de control -> Programas -> Activar caracteristicas -> Internet Information Services."
            "Verifique el servicio W3SVC en services.msc (World Wide Web Publishing Service)."
        )
    }
    16 = @{
        Phase = "Navegador"
        Title = "No se encontro Microsoft Edge ni Google Chrome"
        Solutions = @(
            "Instale Microsoft Edge o Google Chrome en el PC de caja."
            "Son necesarios para abrir EsteliPOS en modo aplicacion e impresion silenciosa."
        )
    }
    17 = @{
        Phase = "Inicio del servidor"
        Title = "EsteliPOS no pudo iniciar despues de instalar"
        Solutions = @(
            "Perfil IIS: abra services.msc y verifique que 'World Wide Web Publishing Service' este en ejecucion."
            "Perfil Simple: revise la tarea programada 'EsteliPOS - Servidor'."
            "Ejecute: powershell -File deployment\windows\Start-EsteliPOS.ps1"
            "Revise storage\logs\laravel.log"
        )
    }
    18 = @{
        Phase = "Verificacion post-instalacion"
        Title = "Las pruebas automaticas finales fallaron"
        Solutions = @(
            "Abra el reporte: storage\app\deployment\post-install-report.json"
            "Ejecute: powershell -File deployment\windows\Test-EsteliPOSInstallation.ps1"
            "Ejecute: powershell -File deployment\windows\Diagnose-EsteliPOS.ps1"
            "Compruebe firewall: regla 'EsteliPOS LAN - Puerto 8080' en red Privada."
        )
    }
    19 = @{
        Phase = "Instalacion automatica de PHP"
        Title = "No se pudo descargar o configurar PHP Thread Safe"
        Solutions = @(
            "Verifique conexion a internet en el PC servidor."
            "Coloque un ZIP offline en deployment\windows\assets\php-ts.zip (PHP TS x64 sin -nts- en el nombre)."
            "Instale manualmente PHP TS desde https://windows.php.net/download/ en C:\EsteliPOS\PHP"
            "Agregue C:\EsteliPOS\PHP al PATH del sistema y vuelva a ejecutar Instalar-EsteliPOS.bat"
            "Instale Microsoft Visual C++ Redistributable x64 (vc_redist.x64.exe)."
        )
    }
    99 = @{
        Phase = "Error inesperado"
        Title = "Ocurrio un error no catalogado"
        Solutions = @(
            "Copie el mensaje de error completo de esta ventana."
            "Revise storage\logs\install-*.log y storage\logs\laravel.log"
            "Ejecute: powershell -File deployment\windows\Diagnose-EsteliPOS.ps1"
            "Contacte soporte Northlink con el log adjunto."
        )
    }
}

function Get-EsteliPOSInstallLogPath {
    . (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
    $Root = Get-EsteliPOSProjectRoot
    $LogDir = Join-Path $Root "storage\logs"
    if (-not (Test-Path $LogDir)) {
        New-Item -ItemType Directory -Force -Path $LogDir | Out-Null
    }

    return Join-Path $LogDir ("install-{0}.log" -f (Get-Date -Format "yyyyMMdd-HHmmss"))
}

function Write-InstallLogLine {
    param(
        [string]$LogPath,
        [string]$Line
    )

    if ($LogPath) {
        Add-Content -Path $LogPath -Value $Line -Encoding UTF8
    }
}

function Show-EsteliPOSInstallError {
    param(
        [int]$ExitCode = 99,
        [string]$DetailMessage = "",
        [string[]]$ExtraSolutions = @(),
        [string]$LogPath = ""
    )

    if (-not $Script:EsteliPOSInstallErrorCatalog.ContainsKey($ExitCode)) {
        $ExitCode = 99
    }

    $Info = $Script:EsteliPOSInstallErrorCatalog[$ExitCode]
    $Separator = ("=" * 62)

    Write-Host ""
    Write-Host $Separator -ForegroundColor Red
    Write-Host "  INSTALACION FALLIDA - ESTELIPOS" -ForegroundColor Red
    Write-Host $Separator -ForegroundColor Red
    Write-Host ""
    Write-Host "Codigo de error: $ExitCode" -ForegroundColor Yellow
    Write-Host "Fase: $($Info.Phase)" -ForegroundColor Yellow
    Write-Host "Problema: $($Info.Title)" -ForegroundColor White
    if ($DetailMessage) {
        Write-Host ""
        Write-Host "Detalle: $DetailMessage" -ForegroundColor Gray
    }
    Write-Host ""
    Write-Host "Posibles soluciones:" -ForegroundColor Cyan
    $Step = 1
    foreach ($Solution in ($Info.Solutions + $ExtraSolutions)) {
        if ([string]::IsNullOrWhiteSpace($Solution)) { continue }
        Write-Host "  $Step. $Solution" -ForegroundColor White
        $Step++
    }
    Write-Host ""
    if ($LogPath) {
        Write-Host "Log de instalacion: $LogPath" -ForegroundColor DarkGray
    }
    Write-Host "Documentacion: informes\GUIA_INSTALACION_PRODUCCION_WINDOWS.md" -ForegroundColor DarkGray
    Write-Host $Separator -ForegroundColor Red
    Write-Host ""

    if ($LogPath) {
        Write-InstallLogLine -LogPath $LogPath -Line "[ERROR $ExitCode] $($Info.Phase) - $($Info.Title)"
        if ($DetailMessage) {
            Write-InstallLogLine -LogPath $LogPath -Line "Detalle: $DetailMessage"
        }
        foreach ($Solution in $Info.Solutions) {
            Write-InstallLogLine -LogPath $LogPath -Line "Solucion: $Solution"
        }
    }
}

function Stop-EsteliPOSInstall {
    param(
        [int]$ExitCode = 99,
        [string]$Message = "",
        [string[]]$ExtraSolutions = @(),
        [string]$LogPath = ""
    )

    Show-EsteliPOSInstallError -ExitCode $ExitCode -DetailMessage $Message -ExtraSolutions $ExtraSolutions -LogPath $LogPath
    exit $ExitCode
}

# Guía de Instalación y Puesta en Producción — EsteliPOS (Windows)

**Versión documento:** 1.1  
**Perfil recomendado:** IIS + PHP FastCGI + SQLite  
**Desarrollado por:** Northlink Microsystem

---

## 1. Resumen ejecutivo

EsteliPOS se instala en una **PC servidor Windows** dentro de la red local de la ferretería. No requiere internet para operar después de instalado. Las tablets y otras PCs acceden por HTTP (ej. `http://192.168.1.50:8080`).

| Componente | Tecnología |
|------------|------------|
| Servidor web | IIS + PHP FastCGI (recomendado) o `artisan serve` (simple) |
| Base de datos | SQLite (`database/database.sqlite`) |
| Assets | Compilados en `public/build/` (sin CDN) |
| Impresión | Edge/Chrome modo app + `--kiosk-printing` |

---

## 2. Materiales que debe llevar al cliente

- [ ] ZIP oficial `EsteliPOS-YYYYMMDD-hash-windows.zip` + archivo `.sha256`
- [ ] Instalador **PHP 8.4+ Thread Safe (TS)** para Windows x64
- [ ] (Opcional offline) `urlrewrite2.exe` en `deployment/windows/assets/`
- [ ] Controlador de impresora térmica 80 mm
- [ ] Logo del negocio (PNG/JPG)
- [ ] Correo y contraseña segura del administrador (12+ caracteres)
- [ ] USB o ruta de red para respaldos externos
- [ ] Esta guía impresa o en tablet del técnico

---

## 3. Requisitos de hardware y software

### PC servidor
- Windows 10/11 64 bits
- **8 GB RAM** recomendado (mínimo 4 GB)
- **SSD** recomendado
- 5 GB libres en disco
- Cuenta con permisos de **Administrador**
- Misma red Wi‑Fi privada que tablets (no red de invitados)

### PHP
- Versión **8.4.1 o superior**
- Build **Thread Safe (TS)** — obligatorio para IIS
- En PATH del sistema
- Extensiones: `ctype`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `tokenizer`, `xml`, `zip`

### Navegador en PC de caja
- Microsoft Edge o Google Chrome

---

## 4. Procedimiento de instalación (IIS — recomendado)

### Paso 1 — Verificar integridad del ZIP

```powershell
certutil -hashfile "C:\Ruta\EsteliPOS-....zip" SHA256
```

Compare con el valor del archivo `.sha256` incluido en `releases/`.

### Paso 2 — Extraer en ruta permanente

```
C:\Northlink\EsteliPOS\
├── artisan
├── deployment\windows\
├── public\build\manifest.json
├── public\web.config
└── vendor\
```

**No** debe quedar `C:\Northlink\EsteliPOS\EsteliPOS\` (doble carpeta).

### Paso 3 — Instalar PHP Thread Safe

1. Descargue PHP TS x64 de [windows.php.net](https://windows.php.net/download/)
2. Extraiga en `C:\PHP` (o similar)
3. Agregue `C:\PHP` al **PATH del sistema**
4. Copie `php.ini-production` → `php.ini`
5. Aplique directivas de `deployment/windows/templates/php-production.ini.snippet`
6. Verifique:

```powershell
php -v
php -m
Test-Path (Join-Path (Split-Path (Get-Command php).Source) "php-cgi.exe")
```

El último comando debe devolver `True`.

### Paso 4 — Verificación previa (sin instalar aún)

```powershell
cd C:\Northlink\EsteliPOS
Set-ExecutionPolicy -Scope Process Bypass
.\deployment\windows\Verify-PHP-EsteliPOS.ps1 -ServerProfile IIS
```

**Resultado esperado:** `Entorno listo para Deploy-EsteliPOS.ps1` (exit code 0).

### Paso 5 — Ejecutar instalador

**Opción A — Doble clic (como administrador):**
```
deployment\windows\Install-EsteliPOS.bat
```

**Opción B — PowerShell como administrador:**

```powershell
cd C:\Northlink\EsteliPOS
Set-ExecutionPolicy -Scope Process Bypass
.\deployment\windows\Deploy-EsteliPOS.ps1 -ServerProfile IIS -Port 8080
```

Durante la instalación se solicitará:
- Correo del administrador
- Contraseña segura (12+ caracteres, mayúsculas, números, símbolos)
- Ruta opcional de respaldo externo (USB/red)

### Paso 6 — Qué hace el instalador automáticamente

1. Activa IIS y CGI en Windows
2. Instala IIS URL Rewrite (descarga o archivo local)
3. Registra PHP FastCGI (4 workers por defecto)
4. Crea sitio `EsteliPOS` en puerto 8080 → carpeta `public/`
5. Genera `.env` de producción (SQLite, `APP_DEBUG=false`)
6. Ejecuta migraciones + catálogos + administrador
7. Configura firewall LAN, tarea de arranque y respaldo diario 7:00 PM
8. Crea accesos directos y hoja QR de acceso en red
9. Ejecuta **prueba post-instalación automática**

### Paso 7 — Resultado esperado al finalizar

Debe aparecer en pantalla:

```
DESPLIEGUE COMPLETADO
Perfil de servidor: IIS
Direccion para PC, tablets y otros equipos: http://192.168.x.x:8080
...
INSTALACION VERIFICADA: todas las pruebas pasaron.
```

Se abren automáticamente:
- Hoja HTML con URL + QR + MAC para reserva DHCP
- Acceso directo **EsteliPOS** (modo app del navegador)

Reporte JSON guardado en:
```
storage\app\deployment\post-install-report.json
```

---

## 5. Instalación alternativa (perfil Simple)

Para 1 caja con carga mínima:

```powershell
.\deployment\windows\Deploy-EsteliPOS.ps1 -ServerProfile Simple
```

O ejecute `Install-EsteliPOS-Simple.bat`.

---

## 6. Configuración post-instalación

### 6.1 Reservar IP en el router
Use la IP y MAC de la hoja **EsteliPOS - Acceso en red**.

### 6.2 Impresora térmica
1. Instale controlador oficial
2. Establezca impresora **predeterminada**
3. Papel 80 mm, márgenes mínimos
4. Active **Drawer Kick** / apertura de gaveta tras imprimir

### 6.3 Abrir siempre desde acceso directo
El acceso directo **EsteliPOS** del escritorio usa `--kiosk-printing` para impresión silenciosa.

### 6.4 Configuración en el sistema
Como administrador, configure en **Configuración**:
- Nombre, RUC, dirección, logo
- IVA y moneda
- Numeraciones (facturas, compras, etc.)
- Módulos activos y usuarios

---

## 7. Pruebas obligatorias antes de datos reales

| # | Prueba | Resultado esperado |
|---|--------|-------------------|
| 1 | PC abre EsteliPOS desde acceso directo | Login carga en < 3 s |
| 2 | Tablet accede por URL/QR | Misma pantalla de login |
| 3 | Crear categoría y producto | Sin error 500 |
| 4 | Venta de contado | Stock baja, total correcto |
| 5 | Ticket térmico | Imprime sin diálogo de Windows |
| 6 | Gaveta | Abre al imprimir (si controlador lo soporta) |
| 7 | Compra | Stock sube |
| 8 | Respaldo manual | Archivo `.sqlite` > 0 bytes en `storage\app\backups` |
| 9 | Diagnóstico | `Diagnose-EsteliPOS.ps1` → HTTP OK |

Ejecutar verificación automatizada:

```powershell
.\deployment\windows\Test-EsteliPOSInstallation.ps1
```

---

## 8. Operación diaria

| Acción | Comando / método |
|--------|------------------|
| Iniciar | Reiniciar PC o `Start-EsteliPOS.ps1` |
| Detener | `Stop-EsteliPOS.ps1` |
| Diagnóstico | `Diagnose-EsteliPOS.ps1` |
| Respaldo manual | `Backup-EsteliPOS.ps1` |
| Ver URL/QR | `Show-NetworkAccess.ps1` |
| Actualizar versión | `Update-EsteliPOS.ps1 -UpdateZip "ruta\EsteliPOS-nuevo.zip"` |

---

## 9. Actualización de versión

```powershell
cd C:\Northlink\EsteliPOS
.\deployment\windows\Update-EsteliPOS.ps1 -UpdateZip "D:\Descargas\EsteliPOS-nuevo.zip"
```

El script respalda `.env`, base de datos y configuración, actualiza código, migra y verifica.

---

## 10. Herramientas del paquete

| Script | Función |
|--------|---------|
| `Verify-PHP-EsteliPOS.ps1` | Pre-requisitos antes de instalar |
| `Deploy-EsteliPOS.ps1` | Instalación completa |
| `Test-EsteliPOSInstallation.ps1` | Pruebas post-instalación |
| `Diagnose-EsteliPOS.ps1` | Estado en campo |
| `Update-EsteliPOS.ps1` | Actualización con respaldo |
| `Backup-EsteliPOS.ps1` | Respaldo SQLite |
| `Start/Stop-EsteliPOS.ps1` | Control del servidor |

---

## 11. Generar paquete ZIP (equipo de desarrollo)

En Mac/Linux con repo limpio y commiteado:

```bash
./deployment/build-release.sh
```

Salida: `releases/EsteliPOS-YYYYMMDD-hash-windows.zip`

---

*Fin de la guía. Consulte `REPORTE_PUESTA_EN_PRODUCCION.md` para matriz de fallos y resultados esperados detallados.*

# Reporte de Puesta en Producción — EsteliPOS Windows

**Fecha:** 2026-08-07  
**Versión:** 2.0.0-final  
**Rama:** `versionproduccion1.0`  
**Perfil objetivo:** IIS + PHP FastCGI + SQLite + assets offline

---

## 1. Estado del paquete de producción

| Área | Estado | Notas |
|------|--------|-------|
| Instalador Windows IIS | ✅ Implementado | `Deploy-EsteliPOS.ps1 -ServerProfile IIS` |
| Perfil Simple (fallback) | ✅ Implementado | `Install-EsteliPOS-Simple.bat` |
| Assets offline (Tailwind, Inter, Chart.js) | ✅ Compilados vía Vite | Sin CDN en vistas |
| SQLite tuning (WAL, busy_timeout) | ✅ Configurado | `.env.production.example` |
| Verificación pre-instalación | ✅ | `Verify-PHP-EsteliPOS.ps1` |
| Verificación post-instalación | ✅ | `Test-EsteliPOSInstallation.ps1` |
| Actualización in-place | ✅ | `Update-EsteliPOS.ps1` |
| Respaldos automáticos | ✅ | Tarea 7:00 PM + manual |
| Acceso LAN + QR | ✅ | Hoja HTML offline |
| Impresión silenciosa | ✅ | Acceso directo `--kiosk-printing` |
| Build release script | ✅ | `deployment/build-release.sh` |
| Tests automatizados despliegue | ✅ | `DeploymentArtifactsTest` |

---

## 2. Arquitectura en producción

```
┌─────────────┐     Wi-Fi LAN      ┌──────────────────────────────────┐
│   Tablet    │ ─────────────────► │  PC Servidor Windows             │
│   PC Caja   │   :8080            │  IIS → PHP FastCGI (4 workers)   │
└─────────────┘                    │  Laravel 12 / EsteliPOS          │
                                   │  SQLite (database.sqlite)        │
                                   │  Impresora USB (solo PC caja)    │
                                   └──────────────────────────────────┘
```

**Sin internet requerido** después de instalar (excepto descarga opcional de URL Rewrite en primer install).

---

## 3. Resultados esperados por fase

### Fase A — Verificación previa (`Verify-PHP-EsteliPOS.ps1`)

| Check | Esperado |
|-------|----------|
| PHP en PATH | Versión ≥ 8.4.1 |
| Extensiones | 11 extensiones listadas OK |
| php-cgi.exe (IIS) | Existe (PHP TS) |
| vendor/ | Presente |
| public/build/manifest.json | Presente |
| public/web.config | Presente |
| Edge/Chrome | Instalado |
| Admin | PowerShell como administrador |

**Exit code:** `0`

### Fase B — Instalación (`Deploy-EsteliPOS.ps1`)

| Paso | Esperado |
|------|----------|
| IIS activado | Sin error |
| URL Rewrite | Módulo instalado |
| Sitio EsteliPOS | Puerto 8080, estado Started |
| .env | `APP_ENV=production`, `APP_DEBUG=false` |
| Base de datos | `database.sqlite` creada y migrada |
| Admin | Usuario creado, cambio de contraseña forzado |
| Firewall | Regla TCP 8080 perfil Private |
| Accesos directos | 2 en escritorio |

### Fase C — Post-instalación (`Test-EsteliPOSInstallation.ps1`)

| Check | Esperado |
|-------|----------|
| env_file | PASS |
| app_debug_off | PASS |
| app_env_production | PASS |
| sqlite_configured | PASS |
| database_exists | PASS |
| database_not_empty | PASS (> 1 KB) |
| vendor | PASS |
| frontend_assets | PASS |
| web_config | PASS |
| storage_writable | PASS |
| http_login | PASS (HTTP 200) |
| iis_site_started | PASS (perfil IIS) |
| url_rewrite | PASS (perfil IIS) |
| artisan_about | PASS (environment: production) |

**Mensaje final:** `INSTALACION VERIFICADA: todas las pruebas pasaron.`

### Fase D — Prueba funcional en negocio

| Escenario | Resultado esperado | Tiempo aprox. |
|-----------|-------------------|---------------|
| Login admin | Dashboard carga | < 3 s |
| POS venta contado | Ticket + cambio correcto | < 2 s |
| Impresión ticket | Sin ventana Windows | Inmediato |
| Tablet consulta stock | Misma data que servidor | < 2 s |
| 2 ventas casi simultáneas (IIS) | Ambas completan (puede haber 1-2 s espera SQLite) | < 5 s |
| Respaldo manual | Archivo `.sqlite` en backups/ | < 30 s |

---

## 4. Matriz de fallos — causas y soluciones

### PHP y entorno

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `php no se reconoce` | PHP no en PATH | Agregar carpeta PHP al PATH del **sistema**, reiniciar PowerShell |
| Falta extensión X | php.ini sin extension= | Editar php.ini, descomentar extensión, reiniciar IIS (`iisreset`) |
| No existe php-cgi.exe | PHP NTS instalado | Reinstalar PHP **Thread Safe (TS)** |
| PHP < 8.4.1 | Versión antigua | Actualizar PHP |

### IIS y servidor web

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| HTTP 404 en todas las rutas | URL Rewrite no instalado | Instalar `urlrewrite2.exe` (ver `deployment/windows/assets/README.txt`) |
| HTTP 500 | Permisos storage | Ejecutar instalador como admin; verificar `icacls` en storage/ |
| HTTP 500 | php.ini incorrecto | Revisar `php --ini`, aplicar snippet de producción |
| Sitio no inicia | Puerto 8080 ocupado | Cambiar `-Port 8081` en Deploy y firewall |
| Página sin estilos | Falta public/build | Regenerar ZIP con `npm run build` en build-release |
| Error Vite manifest | Assets no compilados | Incluir `public/build/` en paquete |

### Red y acceso LAN

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| PC local OK, tablet no entra | Red distinta o invitados | Misma Wi‑Fi, red **Privada** |
| Tablet no entra | Firewall | Verificar regla "EsteliPOS LAN - Puerto 8080" |
| IP cambió | DHCP sin reserva | Reservar IP+MAC en router (hoja QR) |
| Conexión lenta | Wi‑Fi débil | Acercar router o usar cable en servidor |

### Base de datos

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `database is locked` | Varias escrituras SQLite | Normal en pico; reintentar; planear MySQL si persiste |
| Base corrupta | Apagado brusco | Restaurar backup `.sqlite` |
| Migraciones fallan | ZIP incompleto | Usar build-release oficial |

### Impresión y caja

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Ventana de impresión aparece | No usa acceso directo | Abrir **EsteliPOS.lnk** del escritorio |
| Ticket cortado | Ancho papel | Configurar 80 mm en controlador |
| Gaveta no abre | Cable/controlador | RJ11 a impresora, activar Drawer Kick |

### Rendimiento

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Cola en hora pico | Perfil Simple (1 hilo) | Migrar a IIS |
| Lentitud general | HDD + antivirus | SSD; excluir carpeta EsteliPOS del scan en tiempo real |
| RAM alta | Muchas apps abiertas | PC dedicada como servidor, 8 GB RAM |

---

## 5. Comandos de emergencia

```powershell
# Diagnóstico completo
cd C:\Northlink\EsteliPOS
.\deployment\windows\Diagnose-EsteliPOS.ps1

# Reiniciar servidor
.\deployment\windows\Stop-EsteliPOS.ps1
.\deployment\windows\Start-EsteliPOS.ps1

# Reiniciar IIS completo
iisreset /restart

# Ver logs
Get-Content storage\logs\laravel.log -Tail 50
Get-Content storage\logs\server-error.log -Tail 50   # solo perfil Simple

# Respaldo inmediato
.\deployment\windows\Backup-EsteliPOS.ps1

# Verificar instalación
.\deployment\windows\Test-EsteliPOSInstallation.ps1
```

---

## 6. Checklist de entrega al cliente

```
[ ] ZIP verificado con SHA256
[ ] Verify-PHP-EsteliPOS.ps1 → OK
[ ] Deploy-EsteliPOS.ps1 → DESPLIEGUE COMPLETADO
[ ] Test-EsteliPOSInstallation.ps1 → VERIFICADA
[ ] IP reservada en router
[ ] Venta de prueba + impresión + gaveta
[ ] Respaldo externo probado
[ ] Admin cambió contraseña inicial
[ ] Configuración negocio (RUC, IVA, logo)
[ ] Cliente firmó acta de entrega
```

---

## 7. Límites conocidos y escalamiento

| Escenario | Perfil actual | Recomendación |
|-----------|---------------|---------------|
| 1-2 cajas | IIS + SQLite | ✅ Suficiente |
| 3+ cajas hora pico | IIS + SQLite | ⚠️ Monitorear; considerar MySQL |
| 5+ cajas simultáneas | — | MySQL + IIS obligatorio |
| Acceso desde Internet | — | **No soportado**; usar VPN si necesario |

---

## 8. Generación del paquete (desarrollo)

```bash
# Repo sin cambios sin commitear
git status   # debe estar limpio

npm run build
./deployment/build-release.sh

# Verificar
ls releases/
php artisan test tests/Feature/DeploymentArtifactsTest.php
```

---

## 9. Conclusión

El paquete EsteliPOS para Windows está **listo para instalación en producción** en ferreterías con:

- 1 PC servidor + tablets en LAN
- Carga moderada (1-3 puntos de venta)
- Operación **sin internet**
- Instalación guiada con verificación automática

**Criterio de éxito:** las tres verificaciones deben pasar antes de cargar datos reales:

1. `Verify-PHP-EsteliPOS.ps1` (previo)
2. `Deploy-EsteliPOS.ps1` (instalación)
3. `Test-EsteliPOSInstallation.ps1` (posterior) + prueba de venta e impresión en sitio

---

*Documentos relacionados:*
- `informes/GUIA_INSTALACION_PRODUCCION_WINDOWS.md` — procedimiento paso a paso
- `deployment/windows/README.txt` — referencia rápida
- `informes/GUIA_INSTALACION_ESTELIPOS_1.0.1.txt` — guía anterior (actualizar hash ZIP al liberar)

# Revisión de preparación para producción

Fecha de revisión: 2026-08-01
Rama revisada: `codex/integracion-contabilidad-configuracion-pos`

## Resultado

El código integrado de ambos desarrolladores queda aprobado como candidato técnico para despliegue después de completar las condiciones previas indicadas al final. La revisión se repitió sobre la integración final y se corrigieron los problemas de compatibilidad detectados.

## Validaciones ejecutadas

- 60 pruebas automatizadas, 328 aserciones: aprobadas después de la integración.
- Catálogo simulado de 5,000 productos: el POS mantuvo una carga inicial limitada y encontró productos adicionales mediante búsqueda bajo demanda.
- Compilación de Vite para producción: aprobada.
- Sintaxis PHP de `app`, `database`, `routes` y `tests`: aprobada.
- Autoload PSR-4 estricto: aprobado.
- Cachés de configuración, rutas y vistas: aprobadas.
- Instalador completo contra MySQL de pruebas: aprobado.
- Migración de índices y unicidad contra MySQL: aprobada.
- Auditoría Composer: 0 vulnerabilidades conocidas.
- Auditoría npm: 0 vulnerabilidades conocidas.

## Riesgos corregidos

- Se eliminaron dependencias con avisos de seguridad y se actualizaron Laravel, Symfony, Dompdf y PhpSpreadsheet.
- Se corrigieron nombres de modelos que fallaban en sistemas Linux sensibles a mayúsculas.
- El sembrado normal ya no crea ventas, compras, clientes, productos ni usuarios de demostración.
- Se agregó `app:install-production` para migrar, sembrar catálogos, crear el administrador y generar cachés.
- La numeración de facturas, proformas y asientos usa bloqueo transaccional y la factura tiene restricción única.
- Ventas, compras, conversiones de proforma y ajustes bloquean la fila de inventario antes de cambiar existencias.
- El POS rechaza cantidades inválidas, stock insuficiente, efectivo insuficiente y crédito no autorizado.
- El precio base del POS y de las proformas se obtiene nuevamente desde el servidor.
- Convertir una proforma genera inventario y asiento contable, y no puede ejecutarse dos veces.
- Se agregaron índices para ventas, cartera, compras, inventario y diario contable.
- Los roles operativos reciben permisos iniciales coherentes y las operaciones de venta están protegidas por acción.
- La integración de reparaciones, marcas, servicios y descuentos fue validada junto con contabilidad, configuración y POS.
- Se preservó el nombre histórico de la migración de roles para evitar que una instalación existente intente crear de nuevo la tabla `roles`.
- Las migraciones y catálogos de reparaciones se ejecutan durante la instalación; ya no se modifica el esquema de la base de datos desde solicitudes web.

## Condiciones obligatorias antes de instalar

1. Usar `.env.production.example`, generar un `APP_KEY` nuevo y cambiar todas las contraseñas.
2. Mantener `APP_ENV=production`, `APP_DEBUG=false` y `SEED_DEMO_DATA=false`.
3. Ejecutar el instalador contra una base vacía y conservar su salida:

   ```bash
   php artisan app:install-production \
     --admin-name="Administrador" \
     --admin-email="correo@cliente.com" \
     --force
   ```

4. Configurar una copia diaria externa de la base MySQL y probar una restauración antes de cargar datos reales. El módulo de respaldos interno sigue marcado como “Próximamente”; no debe considerarse una copia de seguridad.
5. Probar en el equipo del cliente: inicio de sesión, venta de contado, venta a crédito, compra, impresión 80 mm, cierre de caja, inventario, reparaciones y reportes contables.
6. No usar `compose.yaml` tal como está para exponer el sistema a Internet: es el entorno Laravel Sail de desarrollo. El despliegue debe usar una configuración de producción con servidor web, volúmenes persistentes y política de reinicio.

## Criterio de liberación

No cargar datos reales si falta cualquiera de estas tres comprobaciones: prueba posterior a la integración, respaldo restaurable y prueba de impresión en el equipo final.

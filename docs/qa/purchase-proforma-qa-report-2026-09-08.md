# Informe QA — Compras / Proformas de compra

Fecha: 2026-09-08
Entorno: Windows, PHP/Pest, SQLite en memoria y navegador contra una base SQLite QA temporal.
Alcance: pruebas funcionales, validación, reglas de negocio, base de datos, seguridad, integración, extremos, rendimiento, interfaz, auditoría, caja blanca, caja negra y automatización.

## Resultado ejecutivo

El flujo principal funciona: una proforma se crea en estado `ordered`, no ingresa existencias ni genera asientos, puede editar sus líneas, se puede anular sin afectar inventario y al recibirla genera una sola entrada de inventario y el asiento correcto. Las recepciones a crédito, efectivo y transferencia, las monedas extranjeras, las unidades y el rollback por límite de crédito fueron verificados.

No se recomienda aprobar el módulo para producción sin resolver los hallazgos de severidad alta, especialmente la tasa de cambio 1:1 aceptada sin catálogo y la carrera entre edición y recepción.

## Evidencia ejecutada

- Suite específica final: 20 pruebas aprobadas, 190 aserciones y 1 caso TODO documentado.
- Suites relacionadas de compras e integridad: 27 pruebas aprobadas, 223 aserciones.
- Regresión completa: 261 aprobadas, 1 TODO, 1 omitida y 4 fallos ajenos a proformas de compra.
- Migración desde esquema limpio: aprobada sobre SQLite QA temporal.
- Build frontend: aprobado; Vite advierte que Node 20.11.0 es inferior a 20.19+ / 22.12+.
- Rendimiento local: búsqueda del último producto en un catálogo de 2,500 productos asociados entre 11 y 15 ms sobre SQLite.
- Interfaz real: proveedor, catálogo filtrado, línea, cambio de cantidad y totales dinámicos correctos; consola sin errores JavaScript.

## Hallazgos

### Alta — Se acepta una tasa USD→NIO igual a 1 cuando no existe una tasa configurada

El formulario inicia el tipo de cambio en `1`, conserva ese valor al cambiar a USD si el catálogo no tiene una sugerencia y mantiene habilitado Guardar. El servicio acepta cualquier override mayor que cero. Una proforma USD puede guardarse y recibirse como si USD y NIO fueran equivalentes, subvalorando existencias y contabilidad.

Reproducción automatizada: `usd proformas currently accept a one-to-one manual rate when no catalog rate exists`.

### Alta — Edición y recepción concurrentes no comparten el mismo bloqueo

La recepción bloquea la compra con `lockForUpdate`, pero `update()` carga la compra antes de iniciar la transacción y elimina detalles sin bloquear primero la fila padre. Dos solicitudes simultáneas pueden competir entre editar y recibir; según el orden de bloqueos, existe riesgo de stock aplicado con líneas antiguas y posterior retorno del documento a `ordered`, o de interbloqueo.

Recomendación: obtener y bloquear la compra dentro de la transacción de edición, bloquear detalles y volver a comprobar el estado antes de revertir/eliminar/sincronizar.

### Media — No existe auditoría de las mutaciones de proformas de compra

Crear, editar, recibir, anular, eliminar una línea y eliminar la proforma no escriben `audit_logs`. Solo quedan `user_id` y timestamps en la compra, insuficientes para conocer valores anteriores, acción, IP o responsable de un cambio posterior.

Reproducción automatizada: `purchase proforma mutations currently leave no audit-log record`.

### Media — Un producto inactivo puede agregarse enviando la solicitud directamente

La búsqueda visual solo devuelve productos activos, pero `PurchaseRequest` valida únicamente `exists:products,id`. Un usuario con `compras.create` puede omitir la interfaz y registrar una proforma con un producto inactivo o descontinuado.

Reproducción automatizada: `inactive products are currently accepted when the request bypasses the UI search`.

### Media — La interfaz muestra controles que el usuario de solo lectura no puede ejecutar

Los botones Nueva compra, Proforma compras, Editar y Eliminar se renderizan en el listado sin comprobar sus permisos específicos. El servidor sí bloquea correctamente las solicitudes, por lo que no se observó escalación de privilegios, pero la interfaz es inconsistente y provoca redirecciones de error evitables.

Reproducción automatizada: pruebas de usuario `compras.view` y `the purchases list currently exposes mutation controls to a view-only user`.

### Media — No hay límites superiores coherentes con la base de datos

`items` no tiene `max`, y cantidad/precio solo tienen mínimos. Una solicitud autenticada puede enviar miles de líneas o importes mayores que `DECIMAL(10,2)`, con riesgo de agotamiento de recursos o error SQL en MySQL. SQLite no reproduce estrictamente el overflow decimal de producción.

### Media — La proforma altera el costo maestro del proveedor antes de recibirse

`syncPurchaseLines()` actualiza siempre `product_supplier.purchase_price`, incluso cuando `affectInventory` es falso. Anular o eliminar la proforma no restaura ese costo. No afecta stock ni asientos, pero sí modifica datos maestros mientras el documento aún es tentativo.

### Baja — Un recurso hijo ajeno devuelve 302 en vez de 404

`destroyProformaDetail()` ejecuta `abort(404)` dentro de un bloque que captura `RuntimeException`; la excepción HTTP termina convertida en redirección con flash de error. No se eliminan datos ajenos, pero se rompe la semántica HTTP y se oculta el 404.

El caso automatizado se conserva como TODO con la causa exacta.

### Baja — Numeración documental no usa la secuencia configurada

El catálogo contiene secuencias `compra` y `proforma`, pero el listado muestra un fallback `COMP-` basado en el ID. Esto reduce la independencia entre identificador técnico y número documental y no aprovecha `NumberSequence`.

## Controles aprobados

- Autenticación obligatoria y permisos del servidor para lectura, creación, edición, cambio de estado y eliminación.
- Prevención de eliminación cruzada de detalles: no borra el recurso ajeno.
- CSRF mediante middleware web.
- Escape de nombres de proveedores y productos frente a XSS reflejado/almacenado.
- Rechazo de proveedor, bodega, fecha, moneda, tipo de pago, tasa y líneas inválidas sin escrituras parciales.
- Unidad no configurada provoca rollback total.
- La última línea no puede eliminarse.
- Anulación de proforma abierta sin inventario ni contabilidad.
- Recepción a crédito/efectivo/transferencia con stock y asiento correctos.
- Segundo intento de recepción no duplica inventario.
- Exceso del límite de crédito revierte stock, asiento y estado.
- Ticket/PDF de proforma rechazan compras ordinarias.
- Impresión sin CDN y consola del formulario sin errores JavaScript.

## Limitaciones

- No se ejecutaron migraciones ni pruebas destructivas sobre MySQL persistente.
- La concurrencia edición/recepción fue evaluada por caja blanca; requiere una prueba paralela real sobre MySQL para caracterizar el interbloqueo y el orden final exacto.
- La medición de rendimiento es una referencia local sobre SQLite, no una prueba de carga multiusuario ni un SLA de producción.
- Los cuatro fallos de la regresión completa pertenecen a despliegue Windows, estantes, `auth.switch-user` y una expectativa antigua de login; deben atenderse por separado.

# Plan de implementación del módulo de Configuración

Fecha: 2026-08-01
Estado: Etapa 0 y Fases 2–6 implementadas el 2026-08-01. Fases 7–19 pendientes.

## Avance

- [x] Fase 1: auditoría.
- [x] Etapa 0: estabilización inicial de ajustes, acceso, seeders y caché.
- [x] Fase 2: panel principal de Configuración.
- [x] Fase 3: empresa y configuración general.
- [x] Fase 4: gestión avanzada de usuarios.
- [x] Fase 5: consolidación RBAC, roles y permisos.
- [x] Fase 6: catálogo, dependencias y acceso efectivo a módulos.
- [ ] Fases 7–19.

## Principios de ejecución

- Preservar datos y funcionalidades actuales.
- No usar `migrate:fresh`, `db:wipe` ni modificar `.env`.
- Crear exclusivamente migraciones nuevas y reversibles.
- Ejecutar una fase a la vez, con pruebas y reporte antes de continuar.
- No mostrar una opción hasta que tenga efecto real en backend.
- Mantener compatibilidad temporal con el rol legado durante una migración controlada.
- No recalcular documentos históricos al cambiar impuestos, moneda o numeraciones.
- Preservar los cambios contables no confirmados que ya existen en el worktree.

## Etapa 0: estabilización previa

Estado: completada. Se preservó la compatibilidad temporal con `users.role`; la consolidación destructiva de roles duplicados continúa diferida hasta la Fase 5.

Esta etapa es parte técnica obligatoria antes del rediseño de la Fase 2.

### Objetivos

1. Respaldar y diagnosticar roles, pivotes, ajustes y módulos sin exponer secretos.
2. Reparar la doble serialización de ajustes mediante migración idempotente.
3. Consolidar roles duplicados, reasignando pivotes antes de aplicar índices únicos.
4. Asignar un rol Administrador real al administrador legado antes de cambiar middleware.
5. Hacer idempotentes y coherentes los seeders; incorporar `ConfigurationSeeder` al flujo correcto.
6. Definir un registro tipado de configuraciones y una estrategia única de caché.
7. Añadir pruebas de caracterización del comportamiento actual.

### Migraciones nuevas previstas

- Migración de normalización de valores en `settings`, limitada a filas inequívocamente doble serializadas.
- Migración de consolidación de roles y reasignación de `role_user`/`permission_role`.
- Migración para índices únicos efectivos de roles, después de eliminar duplicados.
- Migración para completar campos de módulos y secuencias cuando se aprueben sus contratos.

Cada migración de reparación deberá registrar conteos anteriores/posteriores y tener una reversión segura cuando técnicamente sea posible. Para transformaciones no reversibles se requerirá respaldo y comando de previsualización.

## Fase 2: panel principal

Estado: completada.

- Crear catálogo de secciones y estados de salud.
- Rediseñar `settings.index` con buscador, categorías, breadcrumbs, indicadores y acciones.
- Agregar componentes Blade para tarjeta, encabezado, estado, formulario, confirmación y mensajes.
- Mostrar únicamente funciones implementadas; marcar el resto como pendiente sin controles engañosos.
- Pruebas: acceso, visibilidad por permiso, indicadores y rutas.

## Fase 3: empresa y configuración general

Estado: completada.

- Separar identidad empresarial, localización, documentos y presentación.
- Crear Form Request, service tipado y carga segura de logo/ticket.
- Añadir valores predeterminados, vista previa y auditoría.
- Integrar nombre, moneda, zona horaria y formatos mediante servicios compartidos.
- Migración aditiva para metadatos o archivos solo si el registro clave/valor no es suficiente.

## Fase 4: usuarios

Estado: completada.

- Completar campos de usuario con migración aditiva.
- Añadir búsqueda, filtros, permisos especiales, foto y actividad.
- Registrar `last_login_at`, estado y accesos.
- Implementar cambio forzado de contraseña sin exponer credenciales.
- Proteger al propio usuario y al último administrador.
- Preferir desactivación sobre borrado cuando existan referencias.

## Fase 5: roles y permisos

Estado: completada.

- Unificar definitivamente el modelo RBAC.
- Crear matriz por módulo/acción, selección masiva, clonación y comparación.
- Permitir edición controlada de permisos de roles del sistema.
- Diseñar reasignación antes de eliminar roles en uso.
- Aplicar middleware/policies/gates en backend y condiciones equivalentes en frontend.
- Crear y documentar `SETTINGS_PERMISSIONS_MATRIX.md`.

## Fase 6: módulos

Estado: completada.

- Completar catálogo de módulos reales: ventas, compras, inventario, clientes, proveedores, caja, créditos, contabilidad, reparaciones, proformas, planilla y reportes.
- Modelar dependencias, fecha de activación y acceso por roles.
- Hacer que el menú, dashboard, widgets y rutas consuman un único servicio de módulos.
- Invalidar caché mediante eventos/observers o un service central.
- Confirmar y auditar cambios; bloquear la desactivación insegura.

## Fase 7: numeraciones

- Crear catálogo de tipos de documento y eliminar duplicación de Blade.
- Añadir reinicio anual y metadatos necesarios mediante migración.
- Implementar generación atómica con transacción y `lockForUpdate`.
- Integrar gradualmente cada módulo emisor de documentos.
- Validar retrocesos contra números ya utilizados.

## Fase 8: apariencia

- Corregir el control duplicado de color.
- Implementar tema, color secundario, densidad, fuente y menú.
- Separar ajustes globales y preferencias de usuario.
- Añadir vista previa sin persistencia y controles de contraste.

## Fase 9: seguridad

- Centralizar política de contraseñas y reutilizarla en alta, edición y cambio.
- Integrar tiempo de sesión, throttling, bloqueo temporal y expiración.
- Añadir cierre de otras sesiones, cambio inicial y registro de accesos.
- Mantener 2FA como “no disponible” hasta tener un flujo backend completo.
- Desplegar políticas progresivamente para evitar bloqueos masivos.

## Fase 10: ventas y facturación

- Definir claves tipadas y consumidor único para reglas de venta.
- Integrar descuentos, stock, precio, cliente, comprobante e impresión en controladores/services existentes.
- Añadir pruebas de cada regla y preservar documentos históricos.

## Fase 11: inventario

- Definir reglas tipadas de stock, alertas, lotes, vencimiento, costo y ajustes.
- Integrarlas en movimientos y validaciones existentes.
- Documentar qué opciones requieren nuevas tablas o columnas.

## Fase 12: impuestos

- Integrar el catálogo `Tax` existente al panel de Configuración.
- Evitar una segunda fuente de IVA: retirar o mapear `tax_rate` general de forma compatible.
- Añadir exoneraciones y retenciones mediante estructura flexible y migraciones aditivas.
- Documentar que la parametrización requiere revisión contable local.

## Fase 13: respaldos

- Crear service y almacenamiento aislado con política de retención.
- Respaldar base, storage de negocio, logos y configuración no sensible.
- Implementar historial, descarga y eliminación autorizada.
- Documentar y probar restauración; no declarar éxito solo por crear un archivo.

## Fase 14: auditoría

- Crear service/eventos comunes para acciones administrativas.
- Registrar cambios anteriores/nuevos sin contraseñas ni secretos.
- Añadir pantalla paginada con filtros y severidad.
- Definir retención e índices de consulta.

## Fase 15: diagnóstico

- Crear endpoint de solo lectura con checks independientes y timeouts.
- Ocultar secretos y datos de conexión.
- Proteger acciones operativas con permisos específicos y auditoría.
- Permitir descargar un reporte sanitizado.

## Fase 16: funciones avanzadas

- Priorizar buscador, checklist, historial, valores predeterminados y exportación no sensible.
- Evaluar asistente, perfiles y sucursales solo después de definir arquitectura multiempresa/sucursal.
- No implementar actualización/licencia/mantenimiento como botones decorativos.

## Fase 17: experiencia de usuario

- Unificar navegación, formularios, errores, confirmaciones y estados vacíos.
- Crear modal accesible en lugar de `confirm()`.
- Añadir protección de cambios sin guardar y navegación por teclado.
- Probar móvil, tablet y escritorio.

## Fase 18: arquitectura y calidad

- Mantener controladores delgados mediante Services y Form Requests.
- Crear Policies para recursos y middleware para módulos.
- Usar enums/catálogos para claves y tipos de documentos.
- Eliminar consultas a modelos desde Blade y duplicación de vistas/JS.
- Definir eventos para caché y auditoría.

## Fase 19: pruebas y cierre

- Crear suite Feature/Unit para todos los criterios de aceptación.
- Verificar base real mediante transacciones/fixtures, autorización y efectos backend.
- Ejecutar pruebas en Docker con Laravel 12, PHP 8.5 y MySQL.
- Completar `SETTINGS_FEATURES.md`, `SETTINGS_TEST_REPORT.md` y `SETTINGS_PERMISSIONS_MATRIX.md`.

## Archivos existentes que probablemente se modificarán

### Núcleo y rutas

- `routes/web.php`
- `bootstrap/app.php`
- `app/Providers/AppServiceProvider.php`

### Controladores

- `app/Http/Controllers/SettingsController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/RoleController.php`
- `app/Http/Controllers/AuthController.php`
- Controladores de facturación, compras, inventario, caja, reparaciones, proformas y contabilidad cuando sus ajustes se integren.

### Modelos y middleware

- `app/Models/Setting.php`
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/Module.php`
- `app/Models/NumberSequence.php`
- `app/Models/AuditLog.php`
- `app/Http/Middleware/CheckRole.php`
- `app/Http/Middleware/CheckPermission.php`
- `app/Http/Middleware/CheckModule.php`

### Vistas

- `resources/views/layouts/app.blade.php`
- Todas las vistas bajo `resources/views/settings/`
- Vistas de menú, dashboards y widgets de módulos activables.

### Seeders

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/ConfigurationSeeder.php`
- `database/seeders/RoleSeeder.php`
- `database/seeders/UserSeeder.php`
- `database/seeders/TaxSeeder.php`

## Archivos nuevos previstos

- Form Requests por sección de Configuración.
- Policies para usuarios, roles, módulos, ajustes, secuencias, respaldos y diagnóstico.
- Services para ajustes, empresa, módulos, seguridad, secuencias, auditoría, respaldos y diagnóstico.
- Enums o clases de catálogo para claves, módulos, permisos y tipos de documento.
- Componentes Blade bajo `resources/views/components/settings/`.
- JavaScript modular para vista previa, cambios sin guardar, matriz y confirmaciones.
- Migraciones aditivas y de reparación con nombres fechados nuevos.
- Pruebas Feature y Unit específicas bajo `tests/Feature/Settings/` y `tests/Unit/Settings/`.

Los nombres exactos se definirán al iniciar cada fase para respetar la arquitectura vigente y evitar archivos especulativos.

## Dependencias

- No se recomienda agregar paquetes para las fases 0–12; Laravel, Blade, Tailwind y JavaScript existente son suficientes.
- Respaldos podrían utilizar herramientas del sistema o un paquete mantenido, pero solo después de validar compatibilidad con PHP 8.5, MySQL, Docker y Windows.
- 2FA requeriría una implementación Laravel compatible y un flujo completo; no debe instalarse como parte de la pantalla visual.
- Toda dependencia nueva deberá justificar mantenimiento, seguridad, licencia y soporte multiplataforma.

## Orden de pruebas por fase

1. Pruebas de caracterización antes del cambio.
2. Pruebas unitarias del service/regla.
3. Pruebas Feature de autorización, validación y persistencia.
4. Prueba manual responsive y de navegación.
5. Verificación de logs y auditoría.
6. Registro del resultado en `SETTINGS_TEST_REPORT.md` cuando comience la implementación.

## Puerta de entrada a la Fase 2

No se debería iniciar el rediseño hasta que se apruebe explícitamente:

- la estrategia de consolidación de roles;
- la compatibilidad temporal del campo `users.role`;
- la reparación de valores en `settings`;
- la incorporación idempotente de módulos/permisos faltantes;
- la política de no alterar documentos históricos;
- y el aislamiento de los cambios contables existentes en el worktree.

Una vez aprobados estos puntos, la primera implementación debe ser la Etapa 0 y sus pruebas, seguida por la Fase 2.

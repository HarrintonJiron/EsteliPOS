# Auditoría del módulo de Configuración

Fecha de auditoría: 2026-08-01
Proyecto inspeccionado: EsteliPOS / Agroservicio
Alcance: Fase 1, inspección de solo lectura. No se modificó código ni información de negocio.

## Resumen ejecutivo

El módulo de Configuración tiene una base funcional: panel principal, usuarios, roles, almacenamiento genérico de ajustes, módulos, seguridad, apariencia, numeraciones y auditoría. Sin embargo, actualmente no puede considerarse un centro de administración completo ni seguro.

Los principales problemas son estructurales:

1. La autorización de Configuración depende de la columna legada `users.role`, mientras el sistema también mantiene roles y permisos por tablas pivote. Ambos modelos de autorización están desincronizados.
2. Varias opciones se guardan, pero no se consumen en los módulos reales. Seguridad, moneda, zona horaria, IVA y activación de módulos son en buena medida configuraciones decorativas.
3. Los datos efectivos de `settings` presentan doble serialización: numerosos strings y enteros están almacenados entre comillas JSON. La interfaz muestra valores como `"Celulares la bendición"`, deja IVA vacío y convierte parámetros de seguridad en `0`.
4. La base efectiva contiene 20 roles con slugs repetidos. Los dos usuarios existentes no tienen roles en `role_user`; dependen únicamente del campo legado `role`.
5. Las migraciones y seeders de Configuración están duplicados o descoordinados. Un rollback parcial puede eliminar tablas creadas por otra migración.
6. Desactivar módulos no bloquea rutas ni oculta entradas del menú, y el caché de módulos no se invalida correctamente.
7. No existen pruebas específicas del módulo de Configuración.

Antes de ampliar funcionalidades se necesita una etapa de estabilización compatible con los datos existentes.

## Arquitectura actual

### Rutas y autorización

- Las 23 rutas de Configuración viven bajo `/settings` en `routes/web.php`.
- Todo el grupo utiliza `role:admin`.
- No se utilizan los permisos existentes `configuracion.view` y `configuracion.edit` para proteger acciones individuales.
- `CheckRole` compara exclusivamente `users.role`; ignora `role_user`, `User::isAdmin()`, permisos y estado activo.
- `CheckPermission` sí conoce el modelo de permisos y el estado del usuario, pero no protege las rutas de Configuración.
- `CheckModule` existe, pero las rutas funcionales no utilizan middleware `module:*`.
- Solo existen policies para asientos y períodos contables. No hay policies para usuarios, roles, módulos, ajustes o secuencias.

### Controladores

- `SettingsController` administra el dashboard y cinco formularios mediante métodos GET/POST combinados.
- `UserController` implementa CRUD, activación, eliminación y restablecimiento de contraseña.
- `RoleController` implementa CRUD y sincronización básica de permisos.
- La validación está embebida en los controladores; no hay Form Requests para Configuración.
- `SettingsController::users()` y `SettingsController::roles()` duplican responsabilidades, pero las rutas usan los controladores dedicados.

### Modelos y persistencia

- `Setting`: almacén clave/valor con tipos y caché permanente por clave.
- `Module`: estado, orden, ruta e indicador activo con caché permanente.
- `NumberSequence`: prefijo, número actual, longitud y estado.
- `User`, `Role`, `Permission`: RBAC por pivotes, coexistiendo con `users.role`.
- `AuditLog`: estructura suficiente para valores anteriores/nuevos, IP y agente de usuario.
- No existe un registro tipado de claves de configuración, valores predeterminados y reglas de validación.
- No existe cifrado para futuras configuraciones sensibles.

### Interfaz

- Panel con ocho tarjetas: usuarios, roles, permisos, general, módulos, seguridad, apariencia y numeraciones.
- Blade y Tailwind mantienen la estética general del ERP.
- No existe directorio de componentes Blade para Configuración ni JavaScript específico reutilizable.
- El menú lateral y los accesos rápidos del encabezado están escritos manualmente en `layouts/app.blade.php`.

### Seeders y pruebas

- `ConfigurationSeeder` define módulos, permisos, roles, secuencias y ajustes iniciales.
- `DatabaseSeeder` no invoca `ConfigurationSeeder`.
- `RoleSeeder` usa `insert`, no es idempotente y crea duplicados en ejecuciones sucesivas.
- Solo existen pruebas de ejemplo y del cierre contable; no hay cobertura de Configuración.

## Funcionalidades existentes

### Funcionan parcialmente

- Dashboard con conteos de usuarios, roles, módulos y actividad reciente.
- Listado, creación, edición, consulta y eliminación de usuarios.
- Activación/desactivación y restablecimiento de contraseña de usuarios.
- Asignación de varios roles a un usuario.
- CRUD de roles personalizados y asignación de permisos mediante checkboxes.
- Persistencia básica de datos generales, apariencia y seguridad.
- Administración de estado y orden de módulos.
- Edición de cinco secuencias visibles.
- Catálogo de impuestos funcional dentro de Contabilidad, aunque no está integrado al panel de Configuración.
- Modelo y tabla de auditoría disponibles.

### Funcionan de forma efectiva en otras áreas

- `system_name` y parte de la apariencia se leen desde el layout.
- `system_name` se utiliza en exportaciones contables.
- La secuencia `asiento` se usa en servicios contables.
- El IVA de ventas y compras proviene actualmente del catálogo `Tax`, no del ajuste general `tax_rate`.

## Funcionalidades incompletas o decorativas

### Panel principal

- No incluye empresa, impuestos, ventas/caja, inventario, respaldos, auditoría ni diagnóstico.
- No tiene buscador, categorías, breadcrumbs, checklist ni indicadores accionables.
- La actividad reciente está vacía porque Configuración no registra acciones.

### Empresa y configuración general

- Solo contempla nombre comercial, RUC, dirección, teléfono, correo, moneda, IVA y zona horaria.
- Faltan razón social, ciudad, país, símbolo, formato de fecha, idioma, logos, pie de factura y mensajes.
- Moneda, zona horaria e IVA general no gobiernan consistentemente ventas, compras, reportes o formato de importes.
- No existe carga ni vista previa de imágenes.
- No existe auditoría.

### Usuarios

- Faltan usuario, teléfono, foto, permiso especial, cambio forzado, actividad, búsqueda y filtros.
- `last_login_at` se muestra, pero `AuthController` nunca lo actualiza.
- El estado activo no se valida al iniciar sesión.
- No se protege al último administrador activo.
- La eliminación no tiene una estrategia segura para usuarios referenciados por operaciones.
- El restablecimiento muestra una contraseña nueva en texto plano dentro del mensaje de sesión.

### Roles y permisos

- La pantalla `/settings/permissions` solo muestra “Sección de permisos en desarrollo”.
- No hay matriz, selección masiva, clonación, comparación ni reasignación.
- La vista de edición afirma que un rol del sistema permite modificar permisos, pero el controlador rechaza cualquier actualización de ese rol.
- La vista de detalle de usuario cuenta permisos, pero no los representa correctamente por módulo.
- Los permisos no se aplican de forma homogénea en backend.

### Módulos

- La desactivación solo cambia una fila.
- No oculta menú, dashboard o widgets.
- No bloquea acceso manual por URL.
- No modela dependencias, fecha de activación ni roles con acceso.
- El controlador actualiza directamente y no limpia los cachés `modules.{slug}.active` ni `modules.active`.
- La base efectiva tiene ocho módulos y omite Contabilidad, aunque la interfaz principal muestra ese módulo.

### Numeraciones

- La interfaz solo contempla factura, compra, cotización, recibo y ajuste.
- `asiento` existe, pero no se muestra.
- Faltan nota de crédito, reparación, cierre de caja y reinicio anual.
- Facturación, compras, proformas, recibos y ajustes no consumen actualmente `NumberSequence::getNext`; solo Contabilidad lo hace.
- `getNext` lee e incrementa sin transacción ni bloqueo de fila, con riesgo de duplicados concurrentes.
- No se valida bajar el próximo número contra documentos existentes.
- Desmarcar estado puede no persistir porque los checkboxes ausentes no llegan al arreglo validado.

### Apariencia

- Solo tema, color principal y nombre del sistema.
- El tema oscuro/automático se guarda, pero no se aplica integralmente.
- Hay dos inputs con el mismo nombre `primary_color`; no están sincronizados.
- No hay vista previa en tiempo real, color secundario, densidad, fuente ni preferencia de menú.

### Seguridad

- Se guardan tiempo de sesión, intentos, longitud, composición y 2FA.
- `AuthController`, configuración de sesiones y validación de contraseñas no consumen esos valores.
- No hay rate limiting, bloqueo temporal, expiración, cierre de sesiones, registro de acceso ni cambio forzado.
- La interfaz presenta 2FA como opción, pero no existe backend.
- Desmarcar checkboxes puede no guardar `false`.

### Respaldos, auditoría y diagnóstico

- No existen pantallas ni servicios para respaldos o diagnóstico.
- Existe `AuditLog`, pero el módulo no registra cambios de configuración, usuarios, roles, módulos, contraseñas o secuencias.

## Botones, formularios y enlaces problemáticos

- “Permisos” abre una pantalla de marcador de posición sin acciones.
- “Guardar Seguridad” persiste valores que no afectan la seguridad real.
- “Guardar Módulos” no bloquea ni oculta módulos y puede dejar caché obsoleto.
- “Guardar Apariencia” ofrece tema oscuro/automático sin implementación global completa.
- “Restablecer contraseña” expone la credencial generada en texto plano.
- Los accesos rápidos `/users`, `/calendar` y `/notifications` del encabezado retornan 404.
- El enlace superior de Configuración se renderiza sin la misma condición de administrador del menú lateral.
- Se usan `confirm()` nativos para eliminar roles/usuarios y restablecer contraseñas.

## Problemas de validación

- No existen Form Requests específicos ni reglas centralizadas.
- `tax_rate` se guarda siempre como `string`, perdiendo el tipo `float` definido por el seeder.
- Los campos de seguridad booleanos se guardan como `integer` porque el request entrega `"1"`, no un boolean PHP.
- Checkboxes desmarcados no se normalizan explícitamente a `false`.
- `primary_color` acepta cualquier string de hasta 20 caracteres, sin validar formato o contraste.
- Zona horaria y moneda no validan contra un catálogo permitido completo.
- No hay validación de colisiones ni retrocesos de numeración.
- Formularios de usuario y rol no presentan errores junto a cada campo ni restauran consistentemente `old()`.

## Problemas de seguridad y autorización

Prioridad crítica:

- Un usuario desactivado puede autenticarse; `AuthController` no filtra `is_active`.
- `CheckRole` tampoco comprueba `is_active`.
- La columna legada `users.role` y los pivotes RBAC divergen. Un administrador por pivote puede recibir 403, mientras un usuario con texto `admin` conserva acceso total sin roles asociados.
- No hay autorización por acción para ver/editar configuración, usuarios, roles, módulos o secuencias.
- No se impide desactivar o eliminar al último administrador activo.
- La contraseña aleatoria se expone en texto plano.
- No hay limitación real de intentos, bloqueo temporal, expiración o cierre de otras sesiones.
- No hay auditoría de acciones administrativas.
- Cambios críticos carecen de transacción y confirmación robusta.

## Código duplicado y deuda técnica

- Métodos de usuarios y roles duplicados en `SettingsController`.
- Migraciones duplicadas para `settings`, `permissions`, `modules`, `audit_logs`, `number_sequences`, `role_user` y `permission_role`.
- Algunos `down()` duplicados usan `dropIfExists`; un rollback del lote equivocado puede borrar una tabla que otra migración considera propia.
- Dos generaciones de migraciones de roles coexisten: la tabla original no incorpora las restricciones del esquema posterior.
- `RoleSeeder` y `ConfigurationSeeder` crean catálogos incompatibles y con nombres distintos.
- El layout consulta `Setting` directamente y contiene navegación extensa escrita a mano.
- La vista de secuencias repite bloques casi idénticos en lugar de iterar un catálogo.
- Formularios GET/POST combinados mezclan lectura, validación y escritura en un único método.

## Estado efectivo de los datos

La consulta de solo lectura a la base local mostró:

- 2 usuarios activos.
- Ambos tienen `roles: []` y dependen de `users.role` (`admin` y `user`).
- 20 registros de roles, con repeticiones de Admin, Vendedor y Contable y slugs duplicados.
- Solo el rol Administrador principal tiene permisos; la mayoría tiene cero.
- 35 permisos.
- 8 módulos activos de 8; falta el registro de Contabilidad.
- 19 ajustes; muchos contienen comillas adicionales.
- 0 registros de auditoría.
- `last_login_at` es nulo para ambos usuarios.

No se corrigieron estos datos durante la auditoría.

## Problemas de experiencia de usuario

- El panel comunica capacidades que todavía no existen o no son efectivas.
- Valores generales aparecen con comillas; IVA queda vacío; seguridad muestra ceros; el selector de color cae a negro.
- No hay breadcrumbs ni una navegación interna consistente.
- Falta búsqueda y filtrado en usuarios, roles y módulos.
- No existe aviso de cambios sin guardar.
- Confirmaciones nativas e inconsistentes.
- Validación solo resumida, no cercana al campo.
- No hay estados de carga, ayuda contextual ni indicadores de salud.
- Los roles duplicados hacen que la selección sea ambigua para un administrador.
- La interfaz no explica consecuencias de desactivar módulos o modificar secuencias.
- Tres accesos rápidos visibles llevan a 404.

## Funciones que requieren permisos específicos

Como mínimo, el backend debe distinguir:

- `configuracion.view`
- `configuracion.edit_general`
- `configuracion.manage_company`
- `configuracion.manage_users`
- `configuracion.manage_roles`
- `configuracion.manage_permissions`
- `configuracion.manage_modules`
- `configuracion.manage_sequences`
- `configuracion.manage_appearance`
- `configuracion.manage_security`
- `configuracion.manage_sales`
- `configuracion.manage_inventory`
- `configuracion.manage_taxes`
- `configuracion.manage_backups`
- `configuracion.view_audit`
- `configuracion.view_diagnostics`
- `configuracion.run_diagnostics`

Acciones destructivas o de alto impacto deben tener permisos separados cuando corresponda: eliminar usuarios/roles, restablecer contraseñas, desactivar módulos, retroceder secuencias, eliminar respaldos, limpiar caché y activar mantenimiento.

## Riesgos antes de modificar

1. **Pérdida de acceso:** migrar RBAC sin asignar primero el rol Administrador al usuario actual puede bloquear Configuración.
2. **Datos duplicados:** imponer índices únicos sin consolidar roles y pivotes fallará o romperá referencias.
3. **Datos serializados:** corregir comillas sin una migración idempotente y copia de seguridad puede alterar valores legítimos.
4. **Rollback destructivo:** normalizar migraciones históricas ya ejecutadas exige migraciones nuevas; no se deben editar ni revertir ciegamente las antiguas.
5. **Caché obsoleto:** cambiar módulos o ajustes sin invalidación coherente produce diferencias entre base e interfaz.
6. **Integraciones existentes:** IVA y numeraciones ya tienen fuentes parciales distintas; unificarlas puede cambiar documentos nuevos y nunca debe recalcular históricos.
7. **Usuarios referenciados:** eliminar usuarios puede violar claves foráneas o perder trazabilidad.
8. **Concurrencia:** numeraciones sin bloqueo pueden duplicarse bajo carga.
9. **Sesiones:** aplicar de golpe políticas hoy decorativas puede cerrar sesiones o bloquear usuarios.
10. **Trabajo no confirmado:** el repositorio ya contiene numerosos cambios sin commit de fases contables; cualquier implementación debe aislar y preservar ese trabajo.

## Recomendaciones prioritarias

1. Congelar cambios funcionales de Configuración hasta sanear RBAC, serialización, seeders y caché.
2. Crear migraciones nuevas, aditivas e idempotentes; no reescribir migraciones ejecutadas.
3. Preparar un comando o migración de reparación con vista previa para roles, pivotes y ajustes.
4. Migrar acceso de administrador conservando temporalmente compatibilidad controlada con `users.role`.
5. Separar lectura y actualización con Form Requests, Services y Policies.
6. Crear un registro central de claves, tipos, valores predeterminados y consumidores.
7. Integrar cada opción con backend antes de mostrarla como disponible.
8. Añadir pruebas de autorización y persistencia antes del rediseño visual.

## Verificación ejecutada

- `php artisan route:list --path=settings`: 23 rutas de Configuración registradas.
- `php artisan migrate:status`: todas las migraciones actuales aparecen ejecutadas; confirmó la coexistencia de migraciones duplicadas.
- Inspección manual local de las ocho pantallas de Configuración y de los accesos rápidos del encabezado.
- `php artisan test`: 5 pruebas pasaron, con 12 aserciones. La suite cubre ejemplos y cierre contable; no contiene pruebas del módulo de Configuración.
- `git diff --check` sobre la documentación: sin errores de formato.

## Conclusión de Fase 1

La arquitectura puede evolucionar sin reescribir todo. Los modelos `Setting`, `Module`, `Permission`, `NumberSequence` y `AuditLog` son reutilizables, pero necesitan contratos tipados, servicios, autorización real, saneamiento de datos y pruebas. No es seguro comenzar por el rediseño del panel sin completar primero la estabilización descrita en `SETTINGS_IMPLEMENTATION_PLAN.md`.

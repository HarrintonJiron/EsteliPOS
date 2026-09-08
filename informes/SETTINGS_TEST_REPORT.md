# Reporte de pruebas del módulo de Configuración

Última actualización: 2026-08-01

## Alcance validado

- Reparación y lectura compatible de ajustes doblemente serializados.
- Persistencia tipada de booleanos y números.
- Acceso de administradores por rol pivote durante la transición legacy.
- Bloqueo de administradores inactivos en login y Configuración.
- Invalidación de caché al cambiar módulos.
- Persistencia de checkboxes de seguridad desmarcados.
- Renderizado del nuevo centro de Configuración.
- Presencia de búsqueda, categorías, estados y secciones planificadas.
- Compatibilidad con las pruebas contables existentes.
- Catálogo completo de módulos, dependencias y módulo núcleo.
- Acceso por rol, ocultamiento del menú y bloqueo de rutas inactivas.
- Desactivación segura, marcas de tiempo, caché y auditoría.
- Configuración de impuestos separada del módulo contable.
- Cálculo coherente del IVA efectivo en POS, proformas y ventas persistidas.
- Impuestos inactivos excluidos del cálculo y del estado predeterminado.

## Resultado automatizado

Comando ejecutado:

```text
docker compose exec -T laravel.test php artisan test --testsuite=Feature
```

Resultado:

- 43 pruebas aprobadas.
- 246 aserciones aprobadas.
- 0 fallas.
- Duración: 5.16 segundos.

## Verificación manual en navegador

- Panel `/settings` renderizado con 15 secciones.
- Búsqueda “impuestos”: 1 resultado correcto.
- Categoría “Sistema”: 5 resultados correctos.
- Viewport móvil de 390 × 844 px sin desbordamiento horizontal.
- Contenido principal móvil: 390 px de ancho.
- Tarjeta móvil: 358 px de ancho.
- Menú lateral oculto inicialmente y operable con botones Abrir/Cerrar.
- Estados `aria-expanded` y overlay verificados.
- Formularios generales, seguridad y apariencia muestran valores reparados.
- Formulario de empresa verificado a 390 px sin desbordamiento horizontal.
- Vista previa reutilizable disponible para dos logos.
- Indicador de cambios sin guardar verificado.
- Factura imprimible, factura PDF y recibo renderizados con una venta local existente.
- Login validado con identidad empresarial y campo unificado de correo/usuario.
- Listado de usuarios validado en escritorio y viewport móvil de 390 × 844 px.
- Vista móvil usa tarjetas y no presenta desbordamiento horizontal.
- Detalle, actividad, permisos efectivos y formulario completo verificados.
- Diálogo accesible de confirmación verificado sin ejecutar la acción destructiva.
- Consola sin errores de aplicación; permanece el aviso preexistente de Tailwind CDN.
- Catálogo de impuestos abierto bajo `/settings/taxes`, sin pestañas de Contabilidad.
- POS comprobado con IVA Exento predeterminado: subtotal C$ 8.50, IVA C$ 0.00 y total C$ 8.50.
- Centro de Configuración cerrado con 8/15 áreas disponibles y 7/15 etiquetadas “Próximamente · Versión 2.0”, sin enlaces a pantallas parciales.
- Logo empresarial ampliado y crédito de Northlink Microsystem verificados en la navegación y el inicio de sesión.
- Compatibilidad de `/facturacion/create` verificada mediante redirección al Punto de Venta, evitando referencias a la vista heredada eliminada.
- Atajos operativos del POS y ticket térmico de 80 mm verificados con foco real en campos, datos de venta y formato imprimible.
- Cobros con tarjeta aceptados por el POS y registrados con su referencia bajo la categoría electrónica transferencia/tarjeta.

## Fase 3 validada

- Persistencia completa de datos empresariales.
- Auditoría con usuario, valores anteriores y valores nuevos.
- Carga de logos JPG, PNG y WebP en disco público.
- Rechazo de SVG y formatos no autorizados.
- Límites de tamaño y dimensiones.
- Aplicación de zona horaria e idioma en requests posteriores.
- Integración de identidad, moneda, fecha y mensajes en documentos de venta.

## Fase 4 validada

- Creación y edición con nombre de usuario, teléfono, roles y permisos directos.
- Búsqueda combinada y filtros por estado y rol.
- Contraseñas almacenadas mediante hash y nunca expuestas en respuesta o auditoría.
- Redirección obligatoria para usuarios con contraseña temporal.
- Login exitoso mediante nombre de usuario y actualización de último acceso.
- Protección del propio usuario y del último administrador activo.
- Bloqueo de eliminación de cuentas con historial operativo.
- Auditoría administrativa sin datos sensibles.

## Fase 5 validada

- Acceso independiente por permisos de configuración, usuarios, roles y matriz.
- Identidad inmutable de roles del sistema con permisos editables.
- Protección del catálogo completo para Administrador.
- Clonación exacta de permisos y auditoría de origen/destino.
- Eliminación bloqueada sin reemplazo y reasignación transaccional con reemplazo.
- Matriz global y comparación de roles renderizadas correctamente.
- Consolidación real: 20 roles reducidos a 9, sin slugs duplicados.
- Integridad real: 0 usuarios sin rol pivote y Administrador con 62/62 permisos.
- Rol Contabilidad con 11 permisos operativos y de reportes.
- Listado de 9 roles y sus coberturas verificado en navegador.
- Matriz de 10 columnas visibles con desplazamiento horizontal contenido.
- Vista móvil de 390 × 844 px sin desbordamiento del documento; tabla desplazable de forma independiente.
- Selección masiva del módulo Contabilidad comprobada: 5/6 a 6/6 sin persistir cambios de prueba.
- Comparación Supervisor/Contabilidad validada con 62 permisos y sin errores de consola.

## Fase 6 validada

- Catálogo real de 13 módulos; los 13 quedaron activos después de la migración.
- Configuración marcado como único módulo núcleo y protegido contra desactivación.
- Dependencias críticas verificadas, incluyendo Ventas → Inventario/Clientes y Contabilidad → Ventas/Compras.
- Asignación inicial con privilegio mínimo: Administrador tiene 13/13; los demás roles solo reciben módulos cuyo permiso de vista ya poseían.
- Base real con 41 asignaciones módulo/rol y 63 permisos totales.
- Módulos inactivos desaparecen del menú y sus rutas responden 404.
- Usuarios sin rol autorizado reciben 403 aunque el módulo esté activo.
- El dashboard general consulta y renderiza únicamente KPIs, alertas, tablas y enlaces de módulos autorizados.
- Confirmación, dependencias, accesos por rol y estado núcleo verificados en navegador.
- Vista de escritorio y viewport móvil de 390 × 844 px verificados sin desbordamiento horizontal.

## Migraciones verificadas

- `2026_08_01_000001_repair_serialized_setting_values`: aplicada.
- `2026_08_01_000002_sync_legacy_admin_role_assignments`: aplicada.
- `2026_08_01_000003_add_management_fields_to_users_table`: aplicada.
- `2026_08_01_000004_create_permission_user_table`: aplicada.
- `2026_08_01_000005_consolidate_rbac_roles`: aplicada; 12 respaldos de fusión.
- `2026_08_01_000006_complete_permission_catalog`: aplicada; 62 permisos en 13 módulos.
- `2026_08_01_000007_complete_module_catalog`: aplicada; 13 módulos, 41 asignaciones por rol y 1 módulo núcleo.
- 17 ajustes respaldados y reparados.
- 1 asignación legacy respaldada y sincronizada.

## Pendientes

- Concurrencia de numeraciones: Fase 7.
- Pruebas de fases 8–19 según su implementación.

La suite actual valida la estabilización y las Fases 2–6; no implica que las secciones etiquetadas como planificadas estén implementadas.

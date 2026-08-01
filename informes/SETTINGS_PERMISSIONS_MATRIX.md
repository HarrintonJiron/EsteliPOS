# Matriz RBAC del sistema

Última actualización: 2026-08-01

## Modelo efectivo

- `roles.slug` es único y constituye la identidad estable del rol.
- `role_user` es la fuente autoritativa de roles; `users.role` se conserva únicamente como dato de compatibilidad de escritura.
- Los permisos efectivos de un usuario son la unión de los permisos de sus roles y `permission_user`.
- El rol `admin` conserva automáticamente todo el catálogo para impedir bloqueos administrativos.
- Los roles del sistema permiten cambiar permisos y descripción, pero no nombre, slug ni eliminación.
- Todo cambio de rol, clonación, reasignación o eliminación se registra en `audit_logs`.

## Catálogo por módulo

| Módulo | Acciones |
|---|---|
| inventario | view, create, edit, delete, export, adjust |
| compras | view, create, edit, delete, export, approve |
| ventas | view, create, edit, delete, export |
| clientes | view, create, edit, delete, export |
| proveedores | view, create, edit, delete, export |
| caja | view, open, close, export |
| creditos | view, create, export |
| proformas | view, create, edit, delete, export, convert |
| reparaciones | view, create, edit, delete, export |
| planilla | view, create, edit, export |
| reportes | view, export |
| contabilidad | view, create, edit, delete, export, close_period |
| configuracion | view, edit, manage_users, manage_roles, manage_permissions |

Total activo: 62 permisos en 13 módulos.

## Perfiles iniciales

| Rol | Cobertura inicial |
|---|---|
| Administrador | Todos los permisos, sincronizados automáticamente |
| Cajero | Venta y apertura/cierre de caja |
| Vendedor | Ventas, clientes, consulta de inventario y proformas |
| Bodega | Gestión y ajuste de inventario |
| Compras | Compras, proveedores y consulta de inventario |
| Contabilidad | Contabilidad, reportes y consulta/exportación comercial |
| Contable | Contabilidad y reportes |
| Supervisor | Supervisión transversal sin permisos destructivos ni de configuración |
| Usuario básico | Sin privilegios administrativos por defecto |

Los perfiles se aplican de manera aditiva durante la migración: no se eliminan permisos previamente concedidos.

## Protección de Configuración

| Permiso | Alcance |
|---|---|
| `configuracion.view` | Abrir el centro de Configuración |
| `configuracion.edit` | Persistir ajustes generales |
| `configuracion.manage_users` | CRUD, estado y contraseñas de usuarios |
| `configuracion.manage_roles` | CRUD, clonación, comparación y reasignación de roles |
| `configuracion.manage_permissions` | Consultar la matriz global de permisos |

El menú y las tarjetas administrativas utilizan los mismos permisos que el middleware y las policies del backend.

## Consolidación ejecutada

- Estado anterior: 20 roles con 12 duplicados distribuidos en `admin`, `vendedor` y `contable`.
- Estado posterior: 9 roles, 0 slugs duplicados y 0 usuarios sin rol pivote.
- Se fusionaron usuarios y permisos antes de eliminar cada duplicado.
- Se añadieron índices únicos para `roles.slug` y `role_user(role_id, user_id)`.
- `rbac_role_merge_backups` conserva 12 filas de respaldo para reversión.

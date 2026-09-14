# Catálogo y reglas de módulos

Fecha: 2026-08-01
Estado: Fase 6 aplicada.

## Contrato de acceso

Un usuario puede acceder a una ruta operativa cuando el módulo está activo y se cumple una de estas condiciones:

1. El usuario posee el rol Administrador.
2. Uno de sus roles está asignado al módulo.

Los permisos de acción continúan aplicándose de forma adicional donde la ruta los exige. Desactivar un módulo devuelve 404, lo oculta de la navegación e invalida su caché. Un usuario activo sin un rol autorizado recibe 403.

## Catálogo

| Orden | Módulo | Dependencias |
|---:|---|---|
| 1 | Inventario | — |
| 2 | Compras | Inventario, Proveedores |
| 3 | Ventas | Inventario, Clientes |
| 4 | Clientes | — |
| 5 | Proveedores | — |
| 6 | Caja | Ventas |
| 7 | Créditos | Ventas, Clientes |
| 8 | Proformas | Ventas, Clientes, Inventario |
| 9 | Reparaciones | Clientes, Inventario |
| 10 | Planilla | — |
| 11 | Contabilidad | Ventas, Compras |
| 12 | Reportes | Ventas, Compras, Inventario |
| 13 | Configuración | —; núcleo protegido |

## Reglas administrativas

- No se puede desactivar Configuración.
- No se puede dejar activo un módulo si alguna dependencia quedaría inactiva.
- Cada actualización de estado, orden o roles ocurre en una transacción y genera el evento de auditoría `modules.updated` con valores anteriores y nuevos.
- La migración inicial asigna todos los módulos al Administrador. Los demás roles reciben únicamente los módulos cuyo permiso `*.view` ya tenían, evitando ampliar privilegios.
- Un módulo activo sin roles seleccionados queda disponible solo para Administrador.

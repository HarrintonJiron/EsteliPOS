# Capacitación y carga inicial por sucursal

## Regla principal

La capacitación y la producción usan bases de datos diferentes. La base de capacitación nunca se copia ni se restaura sobre producción.

## Preparar capacitación

1. Instalar EsteliPOS en una carpeta exclusiva para capacitación.
2. Crear las sucursales y vincular una bodega a cada una.
3. Ejecutar primero cada importación sin `--apply` y guardar el resumen mostrado.
4. Importar con `--mode=training --apply`. Los nombres de clientes se anonimizan y las cuentas por cobrar quedan identificadas con `[CAPACITACIÓN]`.
5. Para archivos con precios en cero, revisar el listado. Si se acepta cargarlos bloqueados, usar `--allow-zero-prices`; esos productos permanecen inactivos.
6. Practicar apertura de caja, ventas, créditos, abonos, compras, gastos, arqueo y respaldo.
7. Descartar completamente esta base al terminar la capacitación.

Ejemplo:

```bash
php artisan app:import-branch-data PRINCIPAL productos.xlsx cuentas-por-cobrar.xlsx --mode=training
php artisan app:import-branch-data PRINCIPAL productos.xlsx cuentas-por-cobrar.xlsx --mode=training --apply
```

## Corte para producción

1. Detener operaciones en el sistema anterior y obtener exportaciones finales.
2. Crear un respaldo de EsteliPOS antes de importar.
3. Confirmar sucursal, bodega, cantidad de productos, existencia total, cuentas por cobrar, saldo y SHA-256 de ambos archivos en la vista previa.
4. Resolver existencias negativas, precios en cero y clientes posiblemente duplicados con el responsable del negocio.
5. Aplicar con `--mode=production --apply` solamente después de la aprobación escrita.
6. Ejecutar `php artisan app:check-integrity`.
7. Conciliar por sucursal contra los Excel y guardar los identificadores de lote.
8. Crear un segundo respaldo y abrir operaciones.

## Comportamiento seguro del importador

- El código global se forma con la sucursal y el código anterior, por ejemplo `PRINCIPAL-490`.
- El código anterior queda vinculado permanentemente a su sucursal.
- Cada par de archivos queda identificado por sus checksums y un UUID de lote.
- Repetir exactamente el mismo lote no duplica datos.
- Las diferencias de existencia generan movimientos trazables con referencia `IMPORT-{lote}`.
- Los saldos importados conservan el documento anterior, total, pago previo, sucursal y lote.
- Una importación falla completa dentro de una transacción; no deja una carga parcial.

## Datos que requieren aprobación antes del arranque

- Productos con existencia negativa normalizada a cero.
- Productos con precio de venta cero, que se importan inactivos únicamente con autorización explícita.
- Variantes de nombres que podrían pertenecer al mismo cliente.
- Totales y saldos finales de las cuentas por cobrar de cada sucursal.

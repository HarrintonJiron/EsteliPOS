# Manual básico de Bodegas — EsteliPOS

## ¿Qué es una bodega?

Una **bodega** es una ubicación física donde guardas mercadería:

- Bodega Principal (mostrador)
- Patio / materiales
- Almacén secundario

El mismo producto puede tener stock en varias bodegas a la vez.  
El **stock total** del producto es la suma de todas las bodegas.

---

## Ideas clave

| Concepto | Significado |
|----------|-------------|
| Stock por bodega | Cantidad en una ubicación concreta |
| Stock total | Suma de todas las bodegas |
| Bodega principal | Se usa por defecto en compras/ajustes si no eliges otra |
| Modo automático (POS) | La venta sale de la bodega que tenga existencias |

---

## Flujo diario recomendado

### 1. Entrada de mercadería (compras)
Al registrar una compra, elige **a qué bodega entra** el producto.  
Si no eliges, entra a la bodega principal.

### 2. Ver existencias
Ve a **Inventario → Bodegas**:

- Lista de bodegas con productos y valor estimado
- Entra a una bodega para ver su inventario
- Busca por nombre o código

### 3. Transferencias internas
Ve a **Inventario → Transferencias**:

1. Elige producto
2. Bodega origen y bodega destino
3. Cantidad y nota (opcional)
4. Confirma

También puedes transferir desde el detalle de una bodega.  
Esto genera movimientos de salida/entrada en el kardex y **no cambia el stock total** del producto (solo la ubicación).

### 4. Vender en el POS
En el punto de venta:

1. El selector de bodega inicia en **Automática (según stock)**
2. Puedes vender aunque el producto no esté en la bodega principal
3. El sistema descuenta de la bodega que sí tenga stock
4. Si quieres forzar una bodega, selecciónala en el menú

**Antes del cambio:** si el stock estaba en otra bodega, el POS decía “Sin stock”.  
**Ahora:** se muestra el stock total y se permite facturar.

---

## Pantallas del módulo

### Inventario → Bodegas
- Resumen: bodegas activas, productos con stock, valor estimado
- Tarjetas por bodega
- Actividad reciente

### Ver bodega
- Listado de productos con stock
- Buscador
- Transferencias
- Eliminar (solo si no es principal y no tiene stock)

### Nueva / Editar bodega
- Código, nombre, dirección, ciudad, teléfono
- Marcar como principal
- Activar / desactivar

---

## Reglas importantes

1. **No se elimina** la bodega principal.
2. **No se elimina** una bodega con stock (primero transferir).
3. **No se desactiva** la principal sin marcar otra como principal.
4. Una venta puede descontar de distintas bodegas si los productos están repartidos (modo automático).
5. El stock total del producto se actualiza solo; no lo edites a mano por bodega desde el catálogo genérico.

---

## Problemas frecuentes

### “Sale sin stock en el POS pero sí hay en inventario”
Causas típicas:
- El stock está en otra bodega
- Había una bodega fija seleccionada sin existencias

Solución:
1. Deja el selector en **Automática**
2. Recarga el POS
3. Verifica en **Inventario → Bodegas** dónde está el producto

### “Compré el producto y no aparece”
Revisa a qué bodega entró la compra.  
Si entró a Bodega B, en Bodega A no se verá.

### “Quiero vender solo de una bodega”
Selecciona esa bodega en el POS.  
Si no hay stock ahí, el sistema igual intentará usar otra con existencias para no bloquear la venta.

---

## Resumen de un minuto

1. Las bodegas separan el inventario por lugar.  
2. El stock total = suma de bodegas.  
3. En POS usa **Automática** para vender sin pelearte con la ubicación.  
4. Usa **Transferir** cuando muevas mercadería físicamente.  
5. En compras, indica siempre la bodega de entrada.

---

*Versión: 2.0.0-final · EsteliPOS*

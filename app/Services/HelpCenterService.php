<?php

namespace App\Services;

class HelpCenterService
{
    /** @return array{categories: array<string, string>, articles: array<int, array<string, mixed>>} */
    public function content(): array
    {
        return [
            'categories' => [
                'inicio' => 'Primeros pasos',
                'ventas' => 'Ventas y caja',
                'inventario' => 'Inventario y compras',
                'acceso' => 'Acceso y configuración',
                'soluciones' => 'Soluciones rápidas',
            ],
            'articles' => [
                $this->article('inicio', '¿Cómo comienzo a usar el sistema?', [
                    'Completa los datos de la empresa desde Configuración.',
                    'Registra o revisa bodegas, unidades, categorías y productos.',
                    'Crea los usuarios y asigna únicamente los permisos que necesitan.',
                    'Antes de vender, abre una sesión de caja con el monto inicial correcto.',
                ], ['configuración', 'empresa', 'comenzar', 'inicio']),
                $this->article('inicio', '¿Qué significan los módulos del menú?', [
                    'Facturación/POS registra ventas y emite comprobantes.',
                    'Inventario controla productos, existencias, bodegas y ajustes.',
                    'Compras registra entradas de mercancía y costos.',
                    'Clientes, créditos y caja administran cobros y movimientos diarios.',
                    'Los módulos visibles dependen del rol y los permisos del usuario.',
                ], ['menú', 'módulos', 'pos', 'pantallas']),
                $this->article('inicio', '¿Cómo cierro correctamente mi jornada?', [
                    'Termina las ventas pendientes y verifica los pagos recibidos.',
                    'Cuenta el efectivo físico antes de cerrar la caja.',
                    'Realiza el arqueo y revisa cualquier diferencia mostrada.',
                    'Cierra sesión cuando el arqueo haya quedado guardado.',
                ], ['jornada', 'cerrar', 'arqueo', 'turno']),

                $this->article('ventas', 'No puedo realizar una venta', [
                    'Confirma que tu usuario tenga permiso para crear ventas.',
                    'Verifica que exista una caja abierta para tu usuario.',
                    'Revisa que el producto tenga precio y existencia en la bodega seleccionada.',
                    'Si el mensaje continúa, anota su texto exacto y comunícalo al administrador.',
                ], ['venta', 'vender', 'caja cerrada', 'permiso', 'stock']),
                $this->article('ventas', 'El producto aparece sin existencia', [
                    'Comprueba que estás usando la bodega correcta.',
                    'Consulta el movimiento del producto y sus existencias por bodega.',
                    'Registra una compra, transferencia o ajuste autorizado si corresponde.',
                    'No repitas la venta ni alteres el stock sin comprobar primero el movimiento.',
                ], ['existencia', 'stock', 'bodega', 'producto']),
                $this->article('ventas', 'El ticket no imprime', [
                    'Confirma que la impresora esté encendida, conectada y con papel.',
                    'Selecciona la impresora térmica correcta en la ventana de impresión.',
                    'Usa papel de 80 mm y escala 100 %, sin encabezados ni pies del navegador.',
                    'Puedes reimprimir el comprobante guardado; no registres otra venta.',
                ], ['ticket', 'impresora', 'imprimir', 'papel']),
                $this->article('ventas', '¿Cómo corrijo un método de pago o total?', [
                    'No crees otra operación hasta revisar el comprobante existente.',
                    'Si la venta todavía es editable, usa la acción autorizada correspondiente.',
                    'Si ya afectó caja o contabilidad, solicita al administrador anular o corregir con trazabilidad.',
                    'Nunca modifiques directamente la base de datos.',
                ], ['pago', 'total', 'corregir', 'anular']),

                $this->article('inventario', '¿Cómo registro un producto nuevo?', [
                    'Abre Inventario y selecciona Nuevo producto o Registro rápido.',
                    'Asigna código, descripción, unidad, precio, impuesto y bodega.',
                    'Registra la existencia inicial únicamente si fue comprobada físicamente.',
                    'Guarda y busca nuevamente el código para confirmar el registro.',
                ], ['producto', 'crear', 'código', 'registro rápido']),
                $this->article('inventario', 'Una compra no actualizó el inventario', [
                    'Comprueba que la compra se haya guardado y no quede como borrador o anulada.',
                    'Revisa la bodega indicada en la compra.',
                    'Consulta los movimientos del producto antes y después de la compra.',
                    'No dupliques la compra; entrega al administrador su número y fecha.',
                ], ['compra', 'inventario', 'entrada', 'movimiento']),
                $this->article('inventario', '¿Cuándo debo usar un ajuste de inventario?', [
                    'Úsalo después de un conteo físico para registrar sobrantes, faltantes o correcciones justificadas.',
                    'Escribe una nota clara y conserva el soporte del conteo.',
                    'No uses ajustes para reemplazar compras, ventas o transferencias omitidas.',
                    'Los ajustes requieren permiso especial y quedan registrados.',
                ], ['ajuste', 'conteo', 'faltante', 'sobrante']),

                $this->article('acceso', 'No veo una opción del menú', [
                    'La opción puede estar desactivada o fuera de los permisos de tu rol.',
                    'Cierra sesión y vuelve a entrar si el administrador acaba de modificar tu acceso.',
                    'Solicita al administrador revisar tu usuario, rol, módulo y permiso específico.',
                    'No compartas cuentas para intentar acceder a una función restringida.',
                ], ['permiso', 'menú', 'rol', 'no aparece']),
                $this->article('acceso', 'Olvidé mi contraseña', [
                    'Solicita al administrador que use Configuración → Usuarios → Contraseña.',
                    'El administrador debe asignar una clave temporal y exigir su cambio al ingresar.',
                    'No envíes contraseñas por mensajes públicos ni las escribas en notas visibles.',
                ], ['contraseña', 'clave', 'olvidé', 'acceso']),
                $this->article('acceso', '¿Qué debo revisar antes de cambiar configuraciones?', [
                    'Confirma que el cambio corresponda a la empresa y período correctos.',
                    'Evita cambiar impuestos, secuencias o moneda durante operaciones activas.',
                    'Documenta el valor anterior y prueba el resultado con una operación controlada.',
                    'Las acciones de zona de riesgo deben realizarse únicamente con respaldo.',
                ], ['configuración', 'impuesto', 'secuencia', 'riesgo']),

                $this->article('soluciones', 'La página está lenta o no responde', [
                    'Espera unos segundos y evita pulsar Guardar repetidamente.',
                    'Comprueba que otras páginas del sistema abran y que la red local esté conectada.',
                    'Actualiza una sola vez la página; verifica si la operación ya quedó registrada.',
                    'Si todos los equipos fallan, comunica la hora y pantalla exactas al administrador.',
                ], ['lento', 'cargando', 'no responde', 'red']),
                $this->article('soluciones', 'Aparece un mensaje de error', [
                    'Lee el mensaje completo y toma una captura sin mostrar contraseñas.',
                    'Anota qué estabas haciendo, el documento y la hora del error.',
                    'Verifica si la operación quedó guardada antes de repetirla.',
                    'Entrega esos datos al administrador; no borres archivos ni reinicies la base.',
                ], ['error', 'mensaje', 'captura', 'problema']),
                $this->article('soluciones', 'Otro equipo no puede entrar al sistema', [
                    'Confirma que el equipo principal esté encendido y EsteliPOS iniciado.',
                    'Verifica que ambos equipos estén conectados a la misma red local.',
                    'Usa el acceso de red indicado por el instalador, sin cambiar puertos manualmente.',
                    'Si solo falla un equipo, revisa su conexión o firewall con el administrador.',
                ], ['lan', 'red', 'otro equipo', 'conexión']),
                $this->article('soluciones', '¿Qué información debo enviar al soporte?', [
                    'Nombre de la pantalla y acción realizada.',
                    'Hora aproximada, usuario y número del documento afectado.',
                    'Texto exacto del error y una captura sin datos sensibles.',
                    'Indica si ocurre en uno o en todos los equipos y si se puede repetir.',
                ], ['soporte', 'reporte', 'ayuda', 'información']),
            ],
        ];
    }

    /** @param array<int, string> $steps @param array<int, string> $keywords */
    private function article(string $category, string $question, array $steps, array $keywords): array
    {
        return compact('category', 'question', 'steps', 'keywords');
    }
}

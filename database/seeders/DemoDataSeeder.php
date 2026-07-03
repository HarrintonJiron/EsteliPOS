<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\supplier;
use App\Models\product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClientes();
        $this->seedProveedores();
        $this->seedProductos();
        $this->seedVentas();
        $this->seedCompras();
    }

    private function seedClientes(): void
    {
        $clientes = [
            ['name' => 'Juan Pérez', 'phone' => '8765-4321', 'email' => 'juan@email.com', 'address' => 'Managua, Barrio San Judas'],
            ['name' => 'María García', 'phone' => '8654-3210', 'email' => 'maria@email.com', 'address' => 'León, Centro'],
            ['name' => 'Carlos López', 'phone' => '8543-2109', 'email' => 'carlos@email.com', 'address' => 'Granada, Calle Real'],
            ['name' => 'Ana Martínez', 'phone' => '8432-1098', 'email' => 'ana@email.com', 'address' => 'Masaya, Mercado'],
            ['name' => 'Pedro Sánchez', 'phone' => '8321-0987', 'email' => 'pedro@email.com', 'address' => 'Estelí, Norte'],
            ['name' => 'Finca El Progreso', 'phone' => '8210-9876', 'email' => 'finca@email.com', 'address' => 'Rivas, Zona Rural'],
            ['name' => 'Cooperativa San José', 'phone' => '8109-8765', 'email' => 'coop@email.com', 'address' => 'Matagalpa, Jinotega'],
            ['name' => 'Luis Rodríguez', 'phone' => '8098-7654', 'email' => 'luis@email.com', 'address' => 'Chinandega, Puerto'],
        ];

        foreach ($clientes as $cliente) {
            Client::updateOrCreate(['email' => $cliente['email']], $cliente);
        }
    }

    private function seedProveedores(): void
    {
        $proveedores = [
            ['name' => 'Agroinsumos Centroamericanos', 'contact_name' => 'Roberto Vega', 'phone' => '2255-1100', 'email' => 'ventas@agroinsumos.com', 'address' => 'Managua, Zona Industrial', 'payment_condition' => 'credito', 'credit_limit' => 50000],
            ['name' => 'Semillas del Pacífico', 'contact_name' => 'Diana Flores', 'phone' => '2255-2200', 'email' => 'pedidos@semillas.com', 'address' => 'León, Subtiava', 'payment_condition' => 'contado', 'credit_limit' => 0],
            ['name' => 'Fertilizantes Nacionales', 'contact_name' => 'Miguel Ángel', 'phone' => '2255-3300', 'email' => 'info@fertnacional.com', 'address' => 'Granada, Zona Franca', 'payment_condition' => 'credito', 'credit_limit' => 75000],
            ['name' => 'Equipos Agrícolas SA', 'contact_name' => 'Fernando Ruiz', 'phone' => '2255-4400', 'email' => 'servicio@equipos.com', 'address' => 'Managua, Carretera Norte', 'payment_condition' => 'contado', 'credit_limit' => 0],
            ['name' => 'Insecticidas y Fungicidas CA', 'contact_name' => 'Carmen Silva', 'phone' => '2255-5500', 'email' => 'ventas@plaguicidas.com', 'address' => 'Masaya, Tisma', 'payment_condition' => 'credito', 'credit_limit' => 30000],
        ];

        foreach ($proveedores as $proveedor) {
            supplier::updateOrCreate(['email' => $proveedor['email']], $proveedor);
        }
    }

    private function seedProductos(): void
    {
        $productos = [
            // Semillas
            ['codigo' => 'SEM-001', 'name' => 'Semilla de Maíz Híbrido', 'categoria' => 'Semillas', 'unidad' => 'kg', 'precio_compra' => 120, 'precio_venta' => 150, 'stock' => 500],
            ['codigo' => 'SEM-002', 'name' => 'Semilla de Frijol Rojo', 'categoria' => 'Semillas', 'unidad' => 'kg', 'precio_compra' => 85, 'precio_venta' => 110, 'stock' => 300],
            ['codigo' => 'SEM-003', 'name' => 'Semilla de Arroz', 'categoria' => 'Semillas', 'unidad' => 'kg', 'precio_compra' => 95, 'precio_venta' => 120, 'stock' => 400],
            ['codigo' => 'SEM-004', 'name' => 'Semilla de Sorgo', 'categoria' => 'Semillas', 'unidad' => 'kg', 'precio_compra' => 70, 'precio_venta' => 90, 'stock' => 250],
            
            // Fertilizantes
            ['codigo' => 'FER-001', 'name' => 'Fertilizante 15-15-15', 'categoria' => 'Fertilizantes', 'unidad' => 'qq', 'precio_compra' => 450, 'precio_venta' => 550, 'stock' => 200],
            ['codigo' => 'FER-002', 'name' => 'Urea 46%', 'categoria' => 'Fertilizantes', 'unidad' => 'qq', 'precio_compra' => 380, 'precio_venta' => 480, 'stock' => 350],
            ['codigo' => 'FER-003', 'name' => 'Fosfato Diamónico', 'categoria' => 'Fertilizantes', 'unidad' => 'qq', 'precio_compra' => 520, 'precio_venta' => 650, 'stock' => 150],
            ['codigo' => 'FER-004', 'name' => 'KCl (Cloruro de Potasio)', 'categoria' => 'Fertilizantes', 'unidad' => 'qq', 'precio_compra' => 400, 'precio_venta' => 500, 'stock' => 180],
            
            // Plaguicidas
            ['codigo' => 'PLA-001', 'name' => 'Insecticida Cypermethrin', 'categoria' => 'Plaguicidas', 'unidad' => 'lt', 'precio_compra' => 180, 'precio_venta' => 240, 'stock' => 120],
            ['codigo' => 'PLA-002', 'name' => 'Fungicida Mancozeb', 'categoria' => 'Plaguicidas', 'unidad' => 'kg', 'precio_compra' => 220, 'precio_venta' => 290, 'stock' => 80],
            ['codigo' => 'PLA-003', 'name' => 'Herbicida Glyphosate', 'categoria' => 'Plaguicidas', 'unidad' => 'lt', 'precio_compra' => 150, 'precio_venta' => 200, 'stock' => 200],
            ['codigo' => 'PLA-004', 'name' => 'Insecticida Abamectin', 'categoria' => 'Plaguicidas', 'unidad' => 'lt', 'precio_compra' => 350, 'precio_venta' => 450, 'stock' => 60],
            
            // Equipos
            ['codigo' => 'EQU-001', 'name' => 'Aspersora Manual 20L', 'categoria' => 'Equipos', 'unidad' => 'un', 'precio_compra' => 850, 'precio_venta' => 1200, 'stock' => 40],
            ['codigo' => 'EQU-002', 'name' => 'Manguera Agrícola 1/2"', 'categoria' => 'Equipos', 'unidad' => 'mt', 'precio_compra' => 35, 'precio_venta' => 55, 'stock' => 500],
            ['codigo' => 'EQU-003', 'name' => 'Guantes de Nitrilo', 'categoria' => 'Equipos', 'unidad' => 'par', 'precio_compra' => 45, 'precio_venta' => 75, 'stock' => 200],
            ['codigo' => 'EQU-004', 'name' => 'Fumigadora Motorizada', 'categoria' => 'Equipos', 'unidad' => 'un', 'precio_compra' => 8500, 'precio_venta' => 11500, 'stock' => 15],
            
            // Otros
            ['codigo' => 'INS-001', 'name' => 'Estaca de Bambú 2.5m', 'categoria' => 'Insumos', 'unidad' => 'un', 'precio_compra' => 12, 'precio_venta' => 20, 'stock' => 1000],
            ['codigo' => 'INS-002', 'name' => 'Sacos de Yute', 'categoria' => 'Insumos', 'unidad' => 'un', 'precio_compra' => 25, 'precio_venta' => 40, 'stock' => 500],
            ['codigo' => 'INS-003', 'name' => 'Hilo para Entutorar', 'categoria' => 'Insumos', 'unidad' => 'kg', 'precio_compra' => 180, 'precio_venta' => 250, 'stock' => 80],
        ];

        foreach ($productos as $producto) {
            product::updateOrCreate(['codigo' => $producto['codigo']], $producto);
        }
    }

    private function seedVentas(): void
    {
        $clientes = Client::pluck('id')->toArray();
        $productos = product::all();
        
        // Fechas variadas: últimos 90 días
        $fechas = [];
        for ($i = 0; $i < 90; $i++) {
            $fechas[] = Carbon::now()->subDays($i);
        }
        
        // 30 ventas de prueba
        for ($i = 0; $i < 30; $i++) {
            $fecha = $fechas[array_rand($fechas)];
            $clienteId = $clientes[array_rand($clientes)];
            
            // 1-5 items por venta
            $itemsCount = rand(1, 5);
            $total = 0;
            
            $sale = Sale::create([
                'cliente_id' => $clienteId,
                'total' => 0, // Se actualiza después
                'estado' => rand(0, 10) > 2 ? 'completada' : 'pendiente',
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
            
            for ($j = 0; $j < $itemsCount; $j++) {
                $producto = $productos->random();
                $cantidad = rand(1, 20);
                $precio = $producto->precio_venta;
                $subtotal = $cantidad * $precio;
                $total += $subtotal;
                
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);
            }
            
            $sale->update(['total' => $total]);
        }
    }

    private function seedCompras(): void
    {
        $proveedores = supplier::pluck('id')->toArray();
        $productos = product::all();
        
        $fechas = [];
        for ($i = 0; $i < 60; $i++) {
            $fechas[] = Carbon::now()->subDays($i);
        }
        
        // 20 compras de prueba
        for ($i = 0; $i < 20; $i++) {
            $fecha = $fechas[array_rand($fechas)];
            $proveedorId = $proveedores[array_rand($proveedores)];
            
            $itemsCount = rand(1, 4);
            $total = 0;
            
            $compra = Purchase::create([
                'proveedor_id' => $proveedorId,
                'numero_factura' => 'FAC-' . strtoupper(uniqid()),
                'fecha' => $fecha->format('Y-m-d'),
                'total' => 0,
                'estado' => 'recibida',
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
            
            for ($j = 0; $j < $itemsCount; $j++) {
                $producto = $productos->random();
                $cantidad = rand(10, 100);
                $precio = $producto->precio_compra;
                $subtotal = $cantidad * $precio;
                $total += $subtotal;
                
                PurchaseDetail::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);
                
                // Actualizar stock
                $producto->increment('stock', $cantidad);
            }
            
            $compra->update(['total' => $total]);
        }
    }
}

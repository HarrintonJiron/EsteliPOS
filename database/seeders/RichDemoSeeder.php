<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RichDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedSuppliers();
        $this->seedClients();
        $this->seedProducts();
        $this->seedPurchases();
        $this->seedSales();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Miscelánea', 'description' => 'Artículos varios para tienda y consumo diario'],
            ['name' => 'Ferretería', 'description' => 'Herramientas, tornillería y materiales de construcción'],
            ['name' => 'Jardinería', 'description' => 'Productos para jardín y agricultura urbana'],
            ['name' => 'Construcción', 'description' => 'Materiales y accesorios para obra'],
            ['name' => 'Limpieza', 'description' => 'Productos de limpieza y mantenimiento'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }

    private function seedSuppliers(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'Distribuidora El Roble', 'business_name' => 'Distribuidora El Roble S.A.', 'ruc' => 'J0010000000001', 'contact_name' => 'Roberto Vega', 'phone' => '8888-1001', 'email' => 'roberto@elroble.com', 'city' => 'Managua', 'address' => 'Km 8 carretera norte', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 150000, 'status' => 'active'],
            ['code' => 'SUP-002', 'name' => 'Proveedores del Sur', 'business_name' => 'Proveedores del Sur', 'ruc' => 'J0010000000002', 'contact_name' => 'Diana Flores', 'phone' => '8888-1002', 'email' => 'diana@provsur.com', 'city' => 'León', 'address' => 'Colonia Santa Rosa', 'type' => 'minorista', 'payment_condition' => 'contado', 'credit_limit' => 0, 'status' => 'active'],
            ['code' => 'SUP-003', 'name' => 'Hardware Central', 'business_name' => 'Hardware Central', 'ruc' => 'J0010000000003', 'contact_name' => 'Miguel Álvarez', 'phone' => '8888-1003', 'email' => 'miguel@hardwarecentral.com', 'city' => 'Estelí', 'address' => 'Mercado de la ciudad', 'type' => 'mayorista', 'payment_condition' => 'credito_15', 'credit_limit' => 80000, 'status' => 'active'],
            ['code' => 'SUP-004', 'name' => 'Limpieza Total', 'business_name' => 'Limpieza Total', 'ruc' => 'J0010000000004', 'contact_name' => 'Carmen Silva', 'phone' => '8888-1004', 'email' => 'carmen@limpiezatotal.com', 'city' => 'Masaya', 'address' => 'Bodega La Esperanza', 'type' => 'minorista', 'payment_condition' => 'contado', 'credit_limit' => 0, 'status' => 'active'],
            ['code' => 'SUP-005', 'name' => 'Insumos Agro S.A.', 'business_name' => 'Insumos Agro S.A.', 'ruc' => 'J0010000000005', 'contact_name' => 'José Ortega', 'phone' => '8888-1005', 'email' => 'jose@insumosagro.com', 'city' => 'Granada', 'address' => 'Zona industrial', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 120000, 'status' => 'active'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                $supplier
            );
        }
    }

    private function seedClients(): void
    {
        $clients = [
            ['code' => 'CLI-001', 'name' => 'Juan Pérez', 'business_name' => 'Juan Pérez', 'ruc' => '0010000000001', 'phone' => '8765-4321', 'email' => 'juan@email.com', 'address' => 'Managua, Barrio San Judas', 'credit_enabled' => true, 'credit_limit' => 25000, 'credit_days' => 30],
            ['code' => 'CLI-002', 'name' => 'María García', 'business_name' => 'María García', 'ruc' => '0010000000002', 'phone' => '8654-3210', 'email' => 'maria@email.com', 'address' => 'León, Centro', 'credit_enabled' => true, 'credit_limit' => 18000, 'credit_days' => 15],
            ['code' => 'CLI-003', 'name' => 'Carlos López', 'business_name' => 'Carlos López', 'ruc' => '0010000000003', 'phone' => '8543-2109', 'email' => 'carlos@email.com', 'address' => 'Granada, Calle Real', 'credit_enabled' => false, 'credit_limit' => 0, 'credit_days' => 0],
            ['code' => 'CLI-004', 'name' => 'Ana Martínez', 'business_name' => 'Ana Martínez', 'ruc' => '0010000000004', 'phone' => '8432-1098', 'email' => 'ana@email.com', 'address' => 'Masaya, Mercado', 'credit_enabled' => true, 'credit_limit' => 30000, 'credit_days' => 45],
            ['code' => 'CLI-005', 'name' => 'Pedro Sánchez', 'business_name' => 'Pedro Sánchez', 'ruc' => '0010000000005', 'phone' => '8321-0987', 'email' => 'pedro@email.com', 'address' => 'Estelí, Norte', 'credit_enabled' => true, 'credit_limit' => 22000, 'credit_days' => 30],
            ['code' => 'CLI-006', 'name' => 'Finca El Progreso', 'business_name' => 'Finca El Progreso', 'ruc' => '0010000000006', 'phone' => '8210-9876', 'email' => 'finca@email.com', 'address' => 'Rivas, Zona Rural', 'credit_enabled' => true, 'credit_limit' => 50000, 'credit_days' => 60],
            ['code' => 'CLI-007', 'name' => 'Cooperativa San José', 'business_name' => 'Cooperativa San José', 'ruc' => '0010000000007', 'phone' => '8109-8765', 'email' => 'coop@email.com', 'address' => 'Matagalpa, Jinotega', 'credit_enabled' => true, 'credit_limit' => 40000, 'credit_days' => 30],
            ['code' => 'CLI-008', 'name' => 'Luis Rodríguez', 'business_name' => 'Luis Rodríguez', 'ruc' => '0010000000008', 'phone' => '8098-7654', 'email' => 'luis@email.com', 'address' => 'Chinandega, Puerto', 'credit_enabled' => false, 'credit_limit' => 0, 'credit_days' => 0],
            ['code' => 'CLI-009', 'name' => 'Sofía Navarro', 'business_name' => 'Sofía Navarro', 'ruc' => '0010000000009', 'phone' => '7990-1111', 'email' => 'sofia@email.com', 'address' => 'Jinotega, Centro', 'credit_enabled' => true, 'credit_limit' => 12000, 'credit_days' => 15],
            ['code' => 'CLI-010', 'name' => 'Marlon Castillo', 'business_name' => 'Marlon Castillo', 'ruc' => '0010000000010', 'phone' => '7989-2222', 'email' => 'marlon@email.com', 'address' => 'Tipitapa, Barrio Nuevo', 'credit_enabled' => true, 'credit_limit' => 27000, 'credit_days' => 30],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['email' => $client['email']],
                $client
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::pluck('id', 'name');
        $imageUrls = [
            'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=800&q=80',
        ];

        $products = [
            ['category' => 'Miscelánea', 'name' => 'Escoba de palma', 'code' => 'MIS-001', 'description' => 'Escoba de palma para uso doméstico', 'purchase_price' => 12.50, 'sale_price' => 18.00, 'stock' => 35, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Trapeador industrial', 'code' => 'MIS-002', 'description' => 'Trapeador de fibra con mango', 'purchase_price' => 15.00, 'sale_price' => 22.50, 'stock' => 28, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Cubo de plástico 20 L', 'code' => 'MIS-003', 'description' => 'Cubo resistente para limpieza', 'purchase_price' => 18.00, 'sale_price' => 27.00, 'stock' => 20, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Papel sanitario x4', 'code' => 'MIS-004', 'description' => 'Rollos de papel sanitario', 'purchase_price' => 8.50, 'sale_price' => 12.50, 'stock' => 45, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Detergente líquido 4 L', 'code' => 'MIS-005', 'description' => 'Detergente líquido concentrado', 'purchase_price' => 24.00, 'sale_price' => 36.00, 'stock' => 30, 'unit' => 'galón', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Jabón en barra', 'code' => 'MIS-006', 'description' => 'Jabón de uso diario', 'purchase_price' => 2.50, 'sale_price' => 4.00, 'stock' => 60, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Esponja multiuso', 'code' => 'MIS-007', 'description' => 'Esponja para lavado y limpieza', 'purchase_price' => 3.00, 'sale_price' => 4.50, 'stock' => 70, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Toalla de cocina', 'code' => 'MIS-008', 'description' => 'Toalla absorbente de cocina', 'purchase_price' => 6.00, 'sale_price' => 9.00, 'stock' => 40, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Velas decorativas', 'code' => 'MIS-009', 'description' => 'Velas aromáticas de decoración', 'purchase_price' => 4.50, 'sale_price' => 7.50, 'stock' => 25, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Bolsas de basura 30 L', 'code' => 'MIS-010', 'description' => 'Paquete de bolsas de basura', 'purchase_price' => 10.00, 'sale_price' => 15.00, 'stock' => 50, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Martillo 16 oz', 'code' => 'FER-001', 'description' => 'Martillo para uso general', 'purchase_price' => 25.00, 'sale_price' => 38.00, 'stock' => 15, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Destornillador plano', 'code' => 'FER-002', 'description' => 'Destornillador de punta plana', 'purchase_price' => 8.00, 'sale_price' => 12.00, 'stock' => 22, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Destornillador Phillips', 'code' => 'FER-003', 'description' => 'Destornillador de punta cruz', 'purchase_price' => 8.50, 'sale_price' => 12.50, 'stock' => 20, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Llave ajustable', 'code' => 'FER-004', 'description' => 'Llave ajustable de 10 pulgadas', 'purchase_price' => 30.00, 'sale_price' => 45.00, 'stock' => 14, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Taladro inalámbrico', 'code' => 'FER-005', 'description' => 'Taladro de batería recargable', 'purchase_price' => 160.00, 'sale_price' => 240.00, 'stock' => 8, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Cinta métrica 5 m', 'code' => 'FER-006', 'description' => 'Cinta métrica de 5 metros', 'purchase_price' => 6.50, 'sale_price' => 9.50, 'stock' => 30, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Nivel de burbuja', 'code' => 'FER-007', 'description' => 'Nivel de plástico para albañilería', 'purchase_price' => 14.00, 'sale_price' => 21.00, 'stock' => 12, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Pala de jardín', 'code' => 'FER-008', 'description' => 'Pala ligera para jardín', 'purchase_price' => 22.00, 'sale_price' => 33.00, 'stock' => 16, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Machete de uso general', 'code' => 'FER-009', 'description' => 'Machete para campo y jardín', 'purchase_price' => 18.50, 'sale_price' => 28.00, 'stock' => 10, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Tornillo 2" x 50', 'code' => 'FER-010', 'description' => 'Paquete de tornillos', 'purchase_price' => 5.50, 'sale_price' => 8.50, 'stock' => 60, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Sustrato premium', 'code' => 'JAR-001', 'description' => 'Sustrato para macetas y huertos', 'purchase_price' => 35.00, 'sale_price' => 52.00, 'stock' => 24, 'unit' => 'saco', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Regadera de plástico', 'code' => 'JAR-002', 'description' => 'Regadera de 2 litros', 'purchase_price' => 12.00, 'sale_price' => 18.00, 'stock' => 18, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Tijeras de podar', 'code' => 'JAR-003', 'description' => 'Tijeras de podar de acero', 'purchase_price' => 20.00, 'sale_price' => 30.00, 'stock' => 14, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Abono orgánico 5 kg', 'code' => 'JAR-004', 'description' => 'Abono para plantas y huertos', 'purchase_price' => 22.00, 'sale_price' => 33.00, 'stock' => 26, 'unit' => 'saco', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Semilla de cilantro', 'code' => 'JAR-005', 'description' => 'Semilla de cilantro para huerto', 'purchase_price' => 3.50, 'sale_price' => 5.50, 'stock' => 40, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Semilla de tomate', 'code' => 'JAR-006', 'description' => 'Semilla de tomate de temporada', 'purchase_price' => 4.00, 'sale_price' => 6.50, 'stock' => 38, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Semilla de zanahoria', 'code' => 'JAR-007', 'description' => 'Semilla de zanahoria', 'purchase_price' => 3.20, 'sale_price' => 5.20, 'stock' => 35, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Semilla de chile dulce', 'code' => 'JAR-008', 'description' => 'Semilla de chile dulce', 'purchase_price' => 4.50, 'sale_price' => 7.00, 'stock' => 33, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Lámina galvanizada', 'code' => 'CON-001', 'description' => 'Lámina para techo y cerramiento', 'purchase_price' => 95.00, 'sale_price' => 140.00, 'stock' => 10, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Tubo PVC 1/2"', 'code' => 'CON-002', 'description' => 'Tubo PVC para agua', 'purchase_price' => 8.50, 'sale_price' => 12.50, 'stock' => 48, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Codo PVC 1/2"', 'code' => 'CON-003', 'description' => 'Codo PVC para instalaciones', 'purchase_price' => 2.50, 'sale_price' => 3.80, 'stock' => 80, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Pegamento para PVC', 'code' => 'CON-004', 'description' => 'Pegamento de junta para tubería', 'purchase_price' => 9.00, 'sale_price' => 13.50, 'stock' => 22, 'unit' => 'botella', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Cemento bolsa 50 kg', 'code' => 'CON-005', 'description' => 'Cemento para construcción', 'purchase_price' => 16.00, 'sale_price' => 24.00, 'stock' => 60, 'unit' => 'bolsa', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Arena fina 20 kg', 'code' => 'CON-006', 'description' => 'Arena fina para mezcla', 'purchase_price' => 6.00, 'sale_price' => 9.00, 'stock' => 55, 'unit' => 'saco', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Clavos 2" x 1 kg', 'code' => 'CON-007', 'description' => 'Clavos para carpintería', 'purchase_price' => 4.50, 'sale_price' => 6.80, 'stock' => 40, 'unit' => 'paquete', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Alambre galvanizado', 'code' => 'CON-008', 'description' => 'Alambre para cerramiento', 'purchase_price' => 18.00, 'sale_price' => 27.00, 'stock' => 20, 'unit' => 'rollo', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Cloro líquido 5 L', 'code' => 'LIM-001', 'description' => 'Cloro para desinfección', 'purchase_price' => 14.00, 'sale_price' => 21.00, 'stock' => 25, 'unit' => 'galón', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Desinfectante aerosol', 'code' => 'LIM-002', 'description' => 'Aerosol antibacterial', 'purchase_price' => 7.20, 'sale_price' => 10.80, 'stock' => 34, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Papel toalla', 'code' => 'LIM-003', 'description' => 'Papel toalla industrial', 'purchase_price' => 13.00, 'sale_price' => 19.50, 'stock' => 28, 'unit' => 'rollo', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Guantes de goma', 'code' => 'LIM-004', 'description' => 'Par de guantes de látex', 'purchase_price' => 6.00, 'sale_price' => 9.00, 'stock' => 35, 'unit' => 'par', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Escoba de piso', 'code' => 'LIM-005', 'description' => 'Escoba con mango', 'purchase_price' => 11.00, 'sale_price' => 16.50, 'stock' => 21, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Recogedor de plástico', 'code' => 'LIM-006', 'description' => 'Recogedor de plástico flexible', 'purchase_price' => 9.50, 'sale_price' => 14.00, 'stock' => 20, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Limpiador de pisos', 'code' => 'LIM-007', 'description' => 'Limpiador concentrado para pisos', 'purchase_price' => 16.50, 'sale_price' => 24.50, 'stock' => 19, 'unit' => 'galón', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Esponja abrasiva', 'code' => 'LIM-008', 'description' => 'Esponja para limpieza pesada', 'purchase_price' => 3.20, 'sale_price' => 4.80, 'stock' => 54, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Botella de agua 1 L', 'code' => 'MIS-011', 'description' => 'Botella de agua potable', 'purchase_price' => 1.20, 'sale_price' => 2.00, 'stock' => 90, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Gaseosa 2 L', 'code' => 'MIS-012', 'description' => 'Gaseosa embotellada', 'purchase_price' => 3.80, 'sale_price' => 5.50, 'stock' => 70, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Cerveza ligera', 'code' => 'MIS-013', 'description' => 'Cerveza de 355 ml', 'purchase_price' => 2.40, 'sale_price' => 3.50, 'stock' => 65, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Chocolates surtidos', 'code' => 'MIS-014', 'description' => 'Paquete de chocolates', 'purchase_price' => 4.00, 'sale_price' => 6.00, 'stock' => 55, 'unit' => 'caja', 'status' => 'active'],
            ['category' => 'Miscelánea', 'name' => 'Galletas de avena', 'code' => 'MIS-015', 'description' => 'Galletas de avena 500 g', 'purchase_price' => 3.00, 'sale_price' => 4.50, 'stock' => 45, 'unit' => 'caja', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Sierra manual', 'code' => 'FER-011', 'description' => 'Sierra manual de metal', 'purchase_price' => 26.00, 'sale_price' => 39.00, 'stock' => 12, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Cinta aislante', 'code' => 'FER-012', 'description' => 'Cinta aislante de 10 m', 'purchase_price' => 3.20, 'sale_price' => 4.80, 'stock' => 50, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Cinta adhesiva', 'code' => 'FER-013', 'description' => 'Cinta adhesiva de 2 pulgadas', 'purchase_price' => 2.80, 'sale_price' => 4.20, 'stock' => 44, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Lima de hierro', 'code' => 'FER-014', 'description' => 'Lima para metal y madera', 'purchase_price' => 11.00, 'sale_price' => 16.50, 'stock' => 18, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Pinza de presión', 'code' => 'FER-015', 'description' => 'Pinza de presión para uso general', 'purchase_price' => 15.00, 'sale_price' => 22.00, 'stock' => 16, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Alicate de corte', 'code' => 'FER-016', 'description' => 'Alicate para corte de cables', 'purchase_price' => 13.00, 'sale_price' => 19.50, 'stock' => 17, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Broca de 1/4', 'code' => 'FER-017', 'description' => 'Broca para perforación', 'purchase_price' => 2.50, 'sale_price' => 3.80, 'stock' => 40, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Ferretería', 'name' => 'Broca de 3/8', 'code' => 'FER-018', 'description' => 'Broca para perforación', 'purchase_price' => 2.80, 'sale_price' => 4.20, 'stock' => 38, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Bisagra de puerta', 'code' => 'CON-009', 'description' => 'Bisagra metálica para puertas', 'purchase_price' => 3.00, 'sale_price' => 4.50, 'stock' => 45, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Manija de puerta', 'code' => 'CON-010', 'description' => 'Manija de metal para puertas', 'purchase_price' => 4.50, 'sale_price' => 6.80, 'stock' => 42, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Lámina de zinc', 'code' => 'CON-011', 'description' => 'Lámina de zinc para techo', 'purchase_price' => 88.00, 'sale_price' => 128.00, 'stock' => 12, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Varilla de acero 1/2"', 'code' => 'CON-012', 'description' => 'Varilla de refuerzo', 'purchase_price' => 24.00, 'sale_price' => 36.00, 'stock' => 22, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Construcción', 'name' => 'Malla ciclónica', 'code' => 'CON-013', 'description' => 'Malla para cercado', 'purchase_price' => 32.00, 'sale_price' => 48.00, 'stock' => 16, 'unit' => 'rollo', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Pala para basura', 'code' => 'LIM-009', 'description' => 'Pala con bolsa para basura', 'purchase_price' => 10.00, 'sale_price' => 15.00, 'stock' => 18, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Lavaloza concentrado', 'code' => 'LIM-010', 'description' => 'Lavaloza de cocina', 'purchase_price' => 15.00, 'sale_price' => 22.00, 'stock' => 24, 'unit' => 'galón', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Desengrasante industrial', 'code' => 'LIM-011', 'description' => 'Desengrasante para talleres', 'purchase_price' => 19.00, 'sale_price' => 28.50, 'stock' => 15, 'unit' => 'galón', 'status' => 'active'],
            ['category' => 'Limpieza', 'name' => 'Balde de 10 L', 'code' => 'LIM-012', 'description' => 'Balde de plástico', 'purchase_price' => 9.00, 'sale_price' => 13.50, 'stock' => 27, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Maceta plástica 20 cm', 'code' => 'JAR-009', 'description' => 'Maceta para plantas ornamentales', 'purchase_price' => 7.50, 'sale_price' => 11.50, 'stock' => 30, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Maceta de barro', 'code' => 'JAR-010', 'description' => 'Maceta tradicional de barro', 'purchase_price' => 6.80, 'sale_price' => 10.20, 'stock' => 28, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Bomba de riego', 'code' => 'JAR-011', 'description' => 'Bomba manual para riego', 'purchase_price' => 26.00, 'sale_price' => 39.00, 'stock' => 12, 'unit' => 'unidad', 'status' => 'active'],
            ['category' => 'Jardinería', 'name' => 'Manguera de riego 20 m', 'code' => 'JAR-012', 'description' => 'Manguera para jardín', 'purchase_price' => 18.00, 'sale_price' => 27.50, 'stock' => 18, 'unit' => 'unidad', 'status' => 'active'],
        ];

        foreach ($products as $index => $product) {
            $categoryId = $categories[$product['category']] ?? null;
            if (! $categoryId) {
                continue;
            }

            Product::updateOrCreate(
                ['code' => $product['code']],
                [
                    'category_id' => $categoryId,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'purchase_price' => $product['purchase_price'],
                    'sale_price' => $product['sale_price'],
                    'stock' => $product['stock'],
                    'unit' => $product['unit'],
                    'status' => $product['status'],
                    'image_url' => $imageUrls[$index % count($imageUrls)],
                ]
            );
        }
    }

    private function seedPurchases(): void
    {
        $user = User::first();
        $suppliers = Supplier::pluck('id')->all();
        $products = Product::all();

        if (empty($suppliers) || empty($products) || ! $user) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            $date = Carbon::now()->subDays(rand(1, 90));
            $supplierId = $suppliers[array_rand($suppliers)];
            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'total' => 0,
                'status' => 'completed',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $total = 0;
            $items = $products->random(rand(2, 5));
            foreach ($items as $product) {
                $quantity = rand(10, 60);
                $price = (float) $product->purchase_price;
                $subtotal = round($quantity * $price, 2);
                $total += $subtotal;

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $product->increment('stock', $quantity);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'reference' => 'COMPRA-' . $purchase->id,
                    'note' => 'Ingreso por compra',
                    'user_id' => $user->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            $purchase->update(['total' => round($total, 2)]);
        }
    }

    private function seedSales(): void
    {
        $user = User::first();
        $clients = Client::pluck('id')->all();
        $products = Product::all();

        if (empty($clients) || empty($products) || ! $user) {
            return;
        }

        for ($i = 0; $i < 45; $i++) {
            $date = Carbon::now()->subDays(rand(1, 90));
            $clientId = $clients[array_rand($clients)];
            $paymentType = rand(0, 100) > 35 ? 'credit' : 'cash';
            $status = rand(0, 100) > 15 ? 'completed' : 'pending';

            $sale = Sale::create([
                'invoice_number' => 'INV-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'client_id' => $clientId,
                'user_id' => $user->id,
                'billing_name' => Client::find($clientId)?->name,
                'billing_business_name' => Client::find($clientId)?->business_name,
                'billing_ruc' => Client::find($clientId)?->ruc,
                'billing_phone' => Client::find($clientId)?->phone,
                'billing_email' => Client::find($clientId)?->email,
                'billing_address' => Client::find($clientId)?->address,
                'date' => $date->format('Y-m-d'),
                'due_date' => $paymentType === 'credit' ? $date->copy()->addDays(15)->format('Y-m-d') : null,
                'total' => 0,
                'payment_type' => $paymentType,
                'status' => $status,
                'notes' => 'Venta de prueba generada por el seeder',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $total = 0;
            $items = $products->random(rand(1, 4));
            foreach ($items as $product) {
                $quantity = rand(1, 8);
                $price = (float) $product->sale_price;
                $subtotal = round($quantity * $price, 2);
                $total += $subtotal;

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $quantity);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reference' => 'VENTA-' . $sale->id,
                    'note' => 'Salida por venta',
                    'user_id' => $user->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            $sale->update(['total' => round($total, 2)]);

            if ($paymentType === 'credit' && rand(0, 100) > 30) {
                $paidAmount = round($total * (rand(20, 60) / 100), 2);
                CreditPayment::create([
                    'client_id' => $clientId,
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'payment_date' => $date->copy()->subDays(rand(0, 10))->format('Y-m-d'),
                    'payment_type' => ['cash', 'transfer', 'check', 'other'][array_rand(['cash', 'transfer', 'check', 'other'])],
                    'reference_number' => 'REF-' . strtoupper(substr(uniqid('', true), -8)),
                    'notes' => 'Abono de prueba',
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}

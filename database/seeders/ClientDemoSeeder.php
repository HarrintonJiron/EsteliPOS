<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\NumberSequence;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ClientDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (Product::query()->exists()) {
            $this->command?->warn('Ya hay productos; no se duplican los datos de demostración.');

            return;
        }

        mt_srand(20260817);

        Setting::set('company_name', 'Ferretería El Roble (Demo)', 'string', 'general');
        Setting::set('company_address', 'Costado norte del mercado, Estelí, Nicaragua', 'string', 'general');
        Setting::set('company_phone', '2713-4500', 'string', 'general');
        Setting::set('company_ruc', 'J0310000123456', 'string', 'general');

        $this->seedCategories();
        $this->seedSuppliers();
        $this->seedClients();
        $this->seedProducts();
        $this->seedPurchases();
        $this->seedSales();
    }

    private function seedCategories(): void
    {
        foreach ([
            ['name' => 'Ferretería', 'description' => 'Herramientas, tornillería y ferretería general'],
            ['name' => 'Construcción', 'description' => 'Cemento, varilla, mallas y materiales de obra'],
            ['name' => 'Plomería', 'description' => 'Tubería PVC, conexiones y pegamentos'],
            ['name' => 'Electricidad', 'description' => 'Cables, breakers, tomacorrientes e iluminación'],
            ['name' => 'Pintura', 'description' => 'Pinturas, brochas, rodillos y thinner'],
            ['name' => 'Jardinería', 'description' => 'Mangueras, machetes y productos de patio'],
            ['name' => 'Limpieza', 'description' => 'Cloro, detergentes y utensilios de aseo'],
            ['name' => 'Miscelánea', 'description' => 'Artículos de mostrador y consumo diario'],
        ] as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }

    private function seedSuppliers(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'Distribuidora El Roble', 'business_name' => 'Distribuidora El Roble S.A.', 'ruc' => 'J0310000000001', 'contact_name' => 'Roberto Vega', 'phone' => '8888-1001', 'email' => 'compras@elroble.com', 'city' => 'Managua', 'address' => 'Km 8 carretera norte', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 150000],
            ['code' => 'SUP-002', 'name' => 'Hardware Central', 'business_name' => 'Hardware Central', 'ruc' => 'J0310000000002', 'contact_name' => 'Miguel Álvarez', 'phone' => '8888-1002', 'email' => 'ventas@hardwarecentral.com', 'city' => 'Estelí', 'address' => 'Mercado municipal', 'type' => 'mayorista', 'payment_condition' => 'credito_15', 'credit_limit' => 80000],
            ['code' => 'SUP-003', 'name' => 'Cementos del Norte', 'business_name' => 'Cementos del Norte S.A.', 'ruc' => 'J0310000000003', 'contact_name' => 'Ana Ruiz', 'phone' => '8888-1003', 'email' => 'ana@cementosnorte.com', 'city' => 'Estelí', 'address' => 'Zona industrial', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 200000],
            ['code' => 'SUP-004', 'name' => 'Pinturas Estelí', 'business_name' => 'Pinturas Estelí', 'ruc' => 'J0310000000004', 'contact_name' => 'Carmen Silva', 'phone' => '8888-1004', 'email' => 'carmen@pinturasesteli.com', 'city' => 'Estelí', 'address' => 'Barrio El Calvario', 'type' => 'minorista', 'payment_condition' => 'contado', 'credit_limit' => 0],
            ['code' => 'SUP-005', 'name' => 'Electro Norte', 'business_name' => 'Electro Norte', 'ruc' => 'J0310000000005', 'contact_name' => 'Luis Méndez', 'phone' => '8888-1005', 'email' => 'luis@electronorte.com', 'city' => 'León', 'address' => 'Carretera a Chinandega', 'type' => 'mayorista', 'payment_condition' => 'credito_15', 'credit_limit' => 90000],
            ['code' => 'SUP-006', 'name' => 'PVC y Conexiones', 'business_name' => 'PVC y Conexiones S.A.', 'ruc' => 'J0310000000006', 'contact_name' => 'Diana Flores', 'phone' => '8888-1006', 'email' => 'diana@pvcconexiones.com', 'city' => 'Managua', 'address' => 'Mercado Oriental', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 110000],
            ['code' => 'SUP-007', 'name' => 'Insumos Agro S.A.', 'business_name' => 'Insumos Agro S.A.', 'ruc' => 'J0310000000007', 'contact_name' => 'José Ortega', 'phone' => '8888-1007', 'email' => 'jose@insumosagro.com', 'city' => 'Estelí', 'address' => 'Carretera a Condega', 'type' => 'mayorista', 'payment_condition' => 'credito_30', 'credit_limit' => 120000],
            ['code' => 'SUP-008', 'name' => 'Limpieza Total', 'business_name' => 'Limpieza Total', 'ruc' => 'J0310000000008', 'contact_name' => 'Martha López', 'phone' => '8888-1008', 'email' => 'martha@limpiezatotal.com', 'city' => 'Masaya', 'address' => 'Bodega La Esperanza', 'type' => 'minorista', 'payment_condition' => 'contado', 'credit_limit' => 0],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(
                ['code' => $supplier['code']],
                $supplier + ['status' => 'active']
            );
        }
    }

    private function seedClients(): void
    {
        $clients = [
            ['code' => 'CLI-001', 'name' => 'Juan Pérez', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-010185-0001A', 'phone' => '8765-4321', 'email' => 'juan.perez@demo.local', 'address' => 'Barrio San Antonio, Estelí', 'credit_enabled' => true, 'credit_limit' => 25000, 'credit_days' => 30],
            ['code' => 'CLI-002', 'name' => 'María García', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-150290-0002B', 'phone' => '8654-3210', 'email' => 'maria.garcia@demo.local', 'address' => 'Centro, Estelí', 'credit_enabled' => true, 'credit_limit' => 18000, 'credit_days' => 15],
            ['code' => 'CLI-003', 'name' => 'Carlos López', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-220578-0003C', 'phone' => '8543-2109', 'email' => 'carlos.lopez@demo.local', 'address' => 'Oscar Gamez, Estelí', 'credit_enabled' => false, 'credit_limit' => 0, 'credit_days' => 0],
            ['code' => 'CLI-004', 'name' => 'Ana Martínez', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-080392-0004D', 'phone' => '8432-1098', 'email' => 'ana.martinez@demo.local', 'address' => 'El Calvario, Estelí', 'credit_enabled' => true, 'credit_limit' => 30000, 'credit_days' => 45],
            ['code' => 'CLI-005', 'name' => 'Pedro Sánchez', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-110165-0005E', 'phone' => '8321-0987', 'email' => 'pedro.sanchez@demo.local', 'address' => 'La Trinidad, Estelí', 'credit_enabled' => true, 'credit_limit' => 22000, 'credit_days' => 30],
            ['code' => 'CLI-006', 'name' => 'Sofía Navarro', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-250488-0006F', 'phone' => '8210-9876', 'email' => 'sofia.navarro@demo.local', 'address' => 'Condega, Estelí', 'credit_enabled' => true, 'credit_limit' => 12000, 'credit_days' => 15],
            ['code' => 'CLI-007', 'name' => 'Luis Rodríguez', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-030275-0007G', 'phone' => '8109-8765', 'email' => 'luis.rodriguez@demo.local', 'address' => 'Pueblo Nuevo', 'credit_enabled' => false, 'credit_limit' => 0, 'credit_days' => 0],
            ['code' => 'CLI-008', 'name' => 'Marlon Castillo', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-190199-0008H', 'phone' => '8098-7654', 'email' => 'marlon.castillo@demo.local', 'address' => 'San Nicolás', 'credit_enabled' => true, 'credit_limit' => 27000, 'credit_days' => 30],
            ['code' => 'CLI-009', 'name' => 'Constructora Los Pinos', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Constructora Los Pinos S.A.', 'ruc' => 'J0310000100001', 'phone' => '2713-1100', 'email' => 'lospinos@demo.local', 'address' => 'Carretera a León, Estelí', 'credit_enabled' => true, 'credit_limit' => 150000, 'credit_days' => 45],
            ['code' => 'CLI-010', 'name' => 'Finca El Progreso', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Finca El Progreso', 'ruc' => 'J0310000100002', 'phone' => '2713-1200', 'email' => 'elprogreso@demo.local', 'address' => 'San Juan de Limay', 'credit_enabled' => true, 'credit_limit' => 50000, 'credit_days' => 60],
            ['code' => 'CLI-011', 'name' => 'Cooperativa San José', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Cooperativa San José R.L.', 'ruc' => 'J0310000100003', 'phone' => '2713-1300', 'email' => 'sanjose@demo.local', 'address' => 'La Concordia', 'credit_enabled' => true, 'credit_limit' => 40000, 'credit_days' => 30],
            ['code' => 'CLI-012', 'name' => 'Albañil Independiente', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-140170-0009J', 'phone' => '7989-3333', 'email' => 'albanil@demo.local', 'address' => 'Villa Libertad, Estelí', 'credit_enabled' => true, 'credit_limit' => 8000, 'credit_days' => 15],
            ['code' => 'CLI-013', 'name' => 'Inmobiliaria Norte', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Inmobiliaria Norte', 'ruc' => 'J0310000100004', 'phone' => '2713-1400', 'email' => 'norte@demo.local', 'address' => 'Centro comercial, Estelí', 'credit_enabled' => true, 'credit_limit' => 80000, 'credit_days' => 30],
            ['code' => 'CLI-014', 'name' => 'Taller El Trueno', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Taller El Trueno', 'ruc' => 'J0310000100005', 'phone' => '2713-1500', 'email' => 'trueno@demo.local', 'address' => 'Salida a Managua', 'credit_enabled' => true, 'credit_limit' => 20000, 'credit_days' => 15],
            ['code' => 'CLI-015', 'name' => 'Doña Rosa Alemán', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-050155-0010K', 'phone' => '7877-4444', 'email' => 'rosa.aleman@demo.local', 'address' => 'Barrio 14 de Abril', 'credit_enabled' => false, 'credit_limit' => 0, 'credit_days' => 0],
            ['code' => 'CLI-016', 'name' => 'Municipalidad de Condega', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Alcaldía de Condega', 'ruc' => 'J0310000100006', 'phone' => '2719-2200', 'email' => 'condega@demo.local', 'address' => 'Parque central, Condega', 'credit_enabled' => true, 'credit_limit' => 100000, 'credit_days' => 45],
            ['code' => 'CLI-017', 'name' => 'Electricista López', 'client_type' => Client::TYPE_NATURAL, 'cedula' => '161-170282-0011L', 'phone' => '7766-5555', 'email' => 'electricista@demo.local', 'address' => 'Barrio El Rosario', 'credit_enabled' => true, 'credit_limit' => 15000, 'credit_days' => 15],
            ['code' => 'CLI-018', 'name' => 'Agropecuaria La Esperanza', 'client_type' => Client::TYPE_COMPANY, 'business_name' => 'Agropecuaria La Esperanza', 'ruc' => 'J0310000100007', 'phone' => '2713-1600', 'email' => 'esperanza@demo.local', 'address' => 'Pueblo Nuevo', 'credit_enabled' => true, 'credit_limit' => 60000, 'credit_days' => 30],
        ];

        $priceListId = PriceList::default()?->id;

        foreach ($clients as $client) {
            $payload = $client + [
                'status' => 'active',
                'price_list_id' => $priceListId,
                'mora_enabled' => (bool) $client['credit_enabled'],
                'mora_rate' => $client['credit_enabled'] ? 5 : 0,
                'mora_grace_days' => $client['credit_enabled'] ? 5 : 0,
            ];
            if (($payload['client_type'] ?? Client::TYPE_NATURAL) === Client::TYPE_NATURAL) {
                $payload['business_name'] = $payload['name'];
            }

            Client::query()->updateOrCreate(
                ['code' => $client['code']],
                $payload
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::query()->pluck('id', 'name');
        $unitMap = Unit::query()->pluck('id', 'abbreviation');
        $taxId = Tax::defaultTax()?->id;
        $warehouse = Warehouse::default();
        $priceList = PriceList::default();
        $items = $this->productCatalog();

        foreach ($items as $index => $item) {
            $categoryId = $categories[$item['category']] ?? null;
            if (! $categoryId) {
                continue;
            }

            $unitId = $this->unitId((string) $item['unit'], $unitMap);
            $stock = (float) $item['stock'];

            $product = Product::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'category_id' => $categoryId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'purchase_price' => $item['purchase_price'],
                    'sale_price' => $item['sale_price'],
                    'stock' => $stock,
                    'unit' => $item['unit'],
                    'base_unit_id' => $unitId,
                    'tax_id' => $taxId,
                    'location' => $item['location'] ?? 'A-'.str_pad((string) (($index % 12) + 1), 2, '0', STR_PAD_LEFT),
                    'low_stock_threshold' => max(4, (int) round($stock * 0.15)),
                    'status' => 'active',
                ]
            );

            if ($warehouse) {
                WarehouseStock::query()->updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $stock,
                        'aisle' => $product->location,
                    ]
                );
            }

            if ($priceList && $unitId) {
                PriceListItem::query()->updateOrCreate(
                    [
                        'price_list_id' => $priceList->id,
                        'product_id' => $product->id,
                        'unit_id' => $unitId,
                    ],
                    [
                        'unit_price' => $product->sale_price,
                        'min_quantity' => 1,
                    ]
                );
            }
        }
    }

    /**
     * @return list<array{category: string, name: string, code: string, description: string, purchase_price: float, sale_price: float, stock: int, unit: string, location?: string}>
     */
    private function productCatalog(): array
    {
        $items = [];
        $add = function (string $category, string $prefix, array $rows) use (&$items): void {
            foreach ($rows as $index => $row) {
                $items[] = [
                    'category' => $category,
                    'code' => sprintf('%s-%03d', $prefix, $index + 1),
                    'name' => $row[0],
                    'description' => $row[1],
                    'purchase_price' => $row[2],
                    'sale_price' => $row[3],
                    'stock' => $row[4],
                    'unit' => $row[5],
                ];
            }
        };

        $add('Ferretería', 'FER', [
            ['Martillo 16 oz', 'Martillo de uña para uso general', 25.00, 38.00, 42, 'und'],
            ['Martillo 20 oz', 'Martillo pesado de carpintero', 32.00, 48.00, 28, 'und'],
            ['Destornillador plano', 'Destornillador de punta plana', 8.00, 12.00, 60, 'und'],
            ['Destornillador Phillips', 'Destornillador de punta cruz', 8.50, 12.50, 58, 'und'],
            ['Juego destornilladores 6 pzas', 'Juego mixto plano y Phillips', 45.00, 68.00, 18, 'und'],
            ['Llave ajustable 10"', 'Llave perica de 10 pulgadas', 30.00, 45.00, 24, 'und'],
            ['Llave ajustable 12"', 'Llave perica de 12 pulgadas', 38.00, 57.00, 16, 'und'],
            ['Taladro inalámbrico 20V', 'Taladro de batería recargable', 160.00, 240.00, 8, 'und'],
            ['Cinta métrica 5 m', 'Cinta métrica de 5 metros', 6.50, 9.50, 55, 'und'],
            ['Cinta métrica 8 m', 'Cinta métrica de 8 metros', 9.00, 13.50, 30, 'und'],
            ['Nivel de burbuja 24"', 'Nivel de aluminio para albañilería', 14.00, 21.00, 22, 'und'],
            ['Sierra manual', 'Sierra de mano para madera', 26.00, 39.00, 14, 'und'],
            ['Cinta aislante', 'Cinta aislante negra 10 m', 3.20, 4.80, 80, 'und'],
            ['Cinta adhesiva 2"', 'Cinta de empaque de 2 pulgadas', 2.80, 4.20, 70, 'und'],
            ['Lima de hierro', 'Lima para metal y madera', 11.00, 16.50, 20, 'und'],
            ['Pinza de presión', 'Pinza de presión de uso general', 15.00, 22.00, 18, 'und'],
            ['Alicate de corte', 'Alicate para cables', 13.00, 19.50, 22, 'und'],
            ['Alicate universal', 'Alicate combinado 8 pulgadas', 16.00, 24.00, 20, 'und'],
            ['Caja de herramientas 16"', 'Caja plástica con compartimentos', 48.00, 72.00, 10, 'und'],
            ['Candado 40 mm', 'Candado de hierro con 3 llaves', 18.00, 27.00, 36, 'und'],
            ['Candado 50 mm', 'Candado reforzado', 24.00, 36.00, 24, 'und'],
            ['Grapas 3/8 x 1000', 'Caja de grapas para engrapar', 7.50, 11.50, 40, 'caja'],
            ['Segueta para metal', 'Hoja de segueta 12 pulgadas', 4.00, 6.50, 50, 'und'],
            ['Arco de segueta', 'Arco ajustable para segueta', 22.00, 33.00, 12, 'und'],
        ]);

        foreach (['3/4"', '1"', '1 1/4"', '1 1/2"', '2"', '2 1/2"', '3"', '4"'] as $index => $size) {
            $items[] = [
                'category' => 'Ferretería',
                'code' => sprintf('TOR-%03d', $index + 1),
                'name' => 'Tornillo para madera '.$size,
                'description' => 'Caja de tornillos para madera '.$size,
                'purchase_price' => 5.50 + ($index * 0.80),
                'sale_price' => 8.50 + ($index * 1.20),
                'stock' => 90 - ($index * 4),
                'unit' => 'caja',
            ];
            $items[] = [
                'category' => 'Construcción',
                'code' => sprintf('CLA-%03d', $index + 1),
                'name' => 'Clavos '.$size.' x 1 lb',
                'description' => 'Bolsa de clavos '.$size,
                'purchase_price' => 4.50 + ($index * 0.40),
                'sale_price' => 6.80 + ($index * 0.60),
                'stock' => 70 - ($index * 3),
                'unit' => 'bolsa',
            ];
        }

        foreach (['1/8"', '3/16"', '1/4"', '5/16"', '3/8"', '1/2"', '5/8"', '3/4"'] as $index => $size) {
            $items[] = [
                'category' => 'Ferretería',
                'code' => sprintf('BRO-%03d', $index + 1),
                'name' => 'Broca para concreto '.$size,
                'description' => 'Broca de widia '.$size,
                'purchase_price' => 3.50 + ($index * 1.10),
                'sale_price' => 5.50 + ($index * 1.60),
                'stock' => 40,
                'unit' => 'und',
            ];
        }

        $add('Construcción', 'CON', [
            ['Cemento bolsa 42.5 kg', 'Cemento gris para construcción', 16.00, 24.00, 120, 'bolsa'],
            ['Arena fina 20 kg', 'Arena fina para mezcla', 6.00, 9.00, 80, 'saco'],
            ['Piedrín 20 kg', 'Piedrín para concreto', 7.00, 10.50, 70, 'saco'],
            ['Cal hidratada 25 kg', 'Cal para revoque', 9.00, 13.50, 40, 'bolsa'],
            ['Varilla de acero 3/8"', 'Varilla de refuerzo 6 m', 18.00, 27.00, 90, 'var'],
            ['Varilla de acero 1/2"', 'Varilla de refuerzo 6 m', 24.00, 36.00, 70, 'var'],
            ['Varilla de acero 5/8"', 'Varilla de refuerzo 6 m', 32.00, 48.00, 40, 'var'],
            ['Alambre de amarre 1 kg', 'Alambre recocido para varilla', 8.00, 12.00, 55, 'und'],
            ['Alambre galvanizado', 'Rollo de alambre para cercado', 18.00, 27.00, 22, 'und'],
            ['Malla ciclónica', 'Malla para cercado 1.8 m', 32.00, 48.00, 16, 'und'],
            ['Lámina galvanizada', 'Lámina para techo y cerramiento', 95.00, 140.00, 18, 'pln'],
            ['Lámina de zinc', 'Lámina de zinc para techo', 88.00, 128.00, 20, 'pln'],
            ['Bisagra de puerta 3.5"', 'Par de bisagras metálicas', 6.00, 9.00, 48, 'und'],
            ['Manija de puerta', 'Manija de metal con cerradura', 28.00, 42.00, 20, 'und'],
            ['Cerradura de sobreponer', 'Cerradura para puerta de madera', 35.00, 52.00, 14, 'und'],
            ['Silicona transparente', 'Tubo de silicona 280 ml', 9.50, 14.50, 36, 'und'],
        ]);

        foreach (['1/2"', '3/4"', '1"', '1 1/4"', '1 1/2"', '2"'] as $index => $size) {
            $items[] = [
                'category' => 'Plomería',
                'code' => sprintf('PVC-%03d', $index + 1),
                'name' => 'Tubo PVC '.$size.' x 6 m',
                'description' => 'Tubo PVC presión '.$size,
                'purchase_price' => 8.50 + ($index * 4.00),
                'sale_price' => 12.50 + ($index * 6.00),
                'stock' => 60 - ($index * 4),
                'unit' => 'und',
            ];
            $items[] = [
                'category' => 'Plomería',
                'code' => sprintf('COD-%03d', $index + 1),
                'name' => 'Codo PVC 90° '.$size,
                'description' => 'Codo PVC para instalaciones '.$size,
                'purchase_price' => 2.50 + ($index * 0.90),
                'sale_price' => 3.80 + ($index * 1.30),
                'stock' => 120 - ($index * 8),
                'unit' => 'und',
            ];
            $items[] = [
                'category' => 'Plomería',
                'code' => sprintf('TEE-%03d', $index + 1),
                'name' => 'Tee PVC '.$size,
                'description' => 'Tee PVC '.$size,
                'purchase_price' => 3.00 + ($index * 1.00),
                'sale_price' => 4.50 + ($index * 1.50),
                'stock' => 90 - ($index * 6),
                'unit' => 'und',
            ];
        }

        $add('Plomería', 'PLO', [
            ['Pegamento PVC 4 oz', 'Cemento solvente para tubería', 9.00, 13.50, 30, 'und'],
            ['Pegamento PVC 8 oz', 'Cemento solvente de 8 onzas', 14.00, 21.00, 22, 'und'],
            ['Teflón 1/2"', 'Cinta teflón para roscas', 1.80, 3.00, 100, 'und'],
            ['Llave de chorro 1/2"', 'Llave cromada para lavamanos', 18.00, 27.00, 16, 'und'],
            ['Llave mezcladora', 'Mezcladora para fregadero', 42.00, 63.00, 10, 'und'],
            ['Sifón para lavamanos', 'Trampa plástica 1 1/4"', 12.00, 18.00, 20, 'und'],
        ]);

        foreach (['12 AWG', '14 AWG', '10 AWG', '8 AWG'] as $index => $gauge) {
            $items[] = [
                'category' => 'Electricidad',
                'code' => sprintf('CAB-%03d', $index + 1),
                'name' => 'Cable THHN '.$gauge.' (metro)',
                'description' => 'Cable de cobre '.$gauge,
                'purchase_price' => 8.00 + ($index * 4.50),
                'sale_price' => 12.00 + ($index * 6.50),
                'stock' => 200 - ($index * 20),
                'unit' => 'm',
            ];
        }

        $add('Electricidad', 'ELE', [
            ['Breaker 20A', 'Interruptor termomagnético 20A', 18.00, 27.00, 24, 'und'],
            ['Breaker 30A', 'Interruptor termomagnético 30A', 22.00, 33.00, 18, 'und'],
            ['Tomacorriente polarizado', 'Toma de pared 110V', 6.50, 9.80, 50, 'und'],
            ['Apagador sencillo', 'Interruptor de pared', 5.50, 8.50, 48, 'und'],
            ['Caja octagonal', 'Caja metálica para lámpara', 8.00, 12.00, 30, 'und'],
            ['Foco LED 9W', 'Bombillo LED luz blanca', 4.50, 7.50, 80, 'und'],
            ['Foco LED 12W', 'Bombillo LED 12 watts', 5.50, 8.50, 60, 'und'],
            ['Extensión 6 m', 'Extensión eléctrica 6 metros', 16.00, 24.00, 18, 'und'],
            ['Cinta aislante blanca', 'Cinta aislante 10 m', 3.20, 4.80, 40, 'und'],
            ['Canaleta 2 m', 'Canaleta plástica para cable', 7.00, 10.50, 28, 'und'],
        ]);

        foreach (['blanco', 'marfil', 'gris', 'azul cielo', 'verde', 'rojo teja', 'amarillo', 'negro'] as $index => $color) {
            $items[] = [
                'category' => 'Pintura',
                'code' => sprintf('PIN-%03d', $index + 1),
                'name' => 'Pintura látex galón '.$color,
                'description' => 'Pintura vinílica color '.$color,
                'purchase_price' => 22.00,
                'sale_price' => 34.00,
                'stock' => 18,
                'unit' => 'gal',
            ];
        }

        $add('Pintura', 'BROC', [
            ['Brocha 2"', 'Brocha de cerda 2 pulgadas', 6.00, 9.00, 40, 'und'],
            ['Brocha 3"', 'Brocha de cerda 3 pulgadas', 8.00, 12.00, 32, 'und'],
            ['Brocha 4"', 'Brocha de cerda 4 pulgadas', 11.00, 16.50, 24, 'und'],
            ['Rodillo 9" con bandeja', 'Kit de rodillo para pared', 18.00, 27.00, 20, 'und'],
            ['Thinner 1/4 gal', 'Thinner para diluir pintura', 9.00, 14.00, 26, 'und'],
            ['Lija #80', 'Pliego de lija gruesa', 1.50, 2.50, 90, 'und'],
            ['Lija #120', 'Pliego de lija media', 1.50, 2.50, 90, 'und'],
            ['Masilla para madera', 'Masilla 1/4 kg', 5.00, 8.00, 22, 'und'],
        ]);

        $add('Jardinería', 'JAR', [
            ['Machete de uso general', 'Machete para campo y patio', 18.50, 28.00, 20, 'und'],
            ['Pala de jardín', 'Pala ligera con mango', 22.00, 33.00, 14, 'und'],
            ['Piocha', 'Piocha para excavación', 28.00, 42.00, 10, 'und'],
            ['Manguera de riego 20 m', 'Manguera para jardín', 18.00, 27.50, 16, 'und'],
            ['Regadera de plástico', 'Regadera de 5 litros', 12.00, 18.00, 18, 'und'],
            ['Tijeras de podar', 'Tijeras de acero', 20.00, 30.00, 12, 'und'],
            ['Abono orgánico 5 kg', 'Abono para plantas y huertos', 22.00, 33.00, 20, 'saco'],
            ['Maceta plástica 20 cm', 'Maceta para ornamentales', 7.50, 11.50, 28, 'und'],
        ]);

        $add('Limpieza', 'LIM', [
            ['Cloro líquido 5 L', 'Cloro para desinfección', 14.00, 21.00, 30, 'gal'],
            ['Detergente líquido 4 L', 'Detergente concentrado', 24.00, 36.00, 22, 'gal'],
            ['Escoba de palma', 'Escoba de palma doméstica', 12.50, 18.00, 26, 'und'],
            ['Trapeador industrial', 'Trapeador de fibra con mango', 15.00, 22.50, 20, 'und'],
            ['Cubo de plástico 20 L', 'Cubo resistente para limpieza', 18.00, 27.00, 16, 'und'],
            ['Guantes de goma', 'Par de guantes de látex', 6.00, 9.00, 40, 'und'],
            ['Bolsas de basura 30 L', 'Paquete de bolsas', 10.00, 15.00, 45, 'caja'],
            ['Lavaloza concentrado', 'Lavaloza de cocina 1 L', 15.00, 22.00, 24, 'und'],
        ]);

        $add('Miscelánea', 'MIS', [
            ['Agua embotellada 1 L', 'Botella de agua potable', 1.20, 2.00, 80, 'und'],
            ['Gaseosa 2 L', 'Gaseosa embotellada', 3.80, 5.50, 40, 'und'],
            ['Pilas AA x4', 'Paquete de pilas alcalinas', 6.00, 9.00, 35, 'und'],
            ['Fósforos caja', 'Caja de fósforos', 1.00, 2.00, 60, 'und'],
            ['Cinta masking tape', 'Cinta de enmascarar 1"', 3.50, 5.50, 30, 'und'],
        ]);

        return $items;
    }

    private function seedPurchases(): void
    {
        $user = User::query()->first();
        $warehouse = Warehouse::default();
        $suppliers = Supplier::query()->get();
        $products = Product::query()->with('baseUnit')->get();
        $inventory = app(InventoryService::class);
        $taxRate = Tax::defaultRate();

        if (! $user || $suppliers->isEmpty() || $products->isEmpty()) {
            return;
        }

        for ($index = 0; $index < 18; $index++) {
            $date = Carbon::now()->subDays(90 - ($index * 4));
            $supplier = $suppliers[$index % $suppliers->count()];
            $purchase = Purchase::query()->create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouse?->id,
                'date' => $date->toDateString(),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'status' => 'completed',
                'currency' => 'NIO',
            ]);
            $purchase->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
            ])->save();

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $lines = collect($products->random(min(4, $products->count())));
            foreach ($lines as $product) {
                $quantity = mt_rand(8, 24);
                $price = (float) $product->purchase_price;
                $lineNet = round($quantity * $price, 2);
                $lineTax = round($lineNet * $taxRate, 2);
                $subtotal += $lineNet;
                $taxTotal += $lineTax;

                PurchaseDetail::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'unit_id' => $product->base_unit_id,
                    'quantity' => $quantity,
                    'base_quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineNet,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                ]);

                $inventory->stockIn(
                    $product,
                    (float) $quantity,
                    'purchase:'.$purchase->id,
                    'Ingreso por compra de demostración',
                    $user->id,
                    $warehouse?->id,
                );
            }

            $purchase->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($subtotal + $taxTotal, 2),
            ]);
        }
    }

    private function seedSales(): void
    {
        $user = User::query()->first();
        $warehouse = Warehouse::default();
        $clients = Client::query()->get();
        $products = Product::query()->with('baseUnit')->get();
        $inventory = app(InventoryService::class);
        $taxRate = Tax::defaultRate();

        if (! $user || $clients->isEmpty() || $products->isEmpty()) {
            return;
        }

        for ($index = 0; $index < 48; $index++) {
            $date = Carbon::now()->subDays(60 - $index);
            $client = $clients[$index % $clients->count()];
            $useCredit = $client->credit_enabled && ($index % 3 !== 0);
            $invoiceNumber = NumberSequence::getNext('factura');

            $sale = Sale::query()->create([
                'invoice_number' => $invoiceNumber,
                'client_id' => $client->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouse?->id,
                'billing_name' => $client->name,
                'billing_business_name' => $client->business_name,
                'billing_document_type' => $client->client_type === Client::TYPE_COMPANY ? 'ruc' : 'cedula',
                'billing_ruc' => $client->ruc ?: $client->cedula,
                'billing_phone' => $client->phone,
                'billing_email' => $client->email,
                'billing_address' => $client->address,
                'date' => $date->toDateString(),
                'due_date' => $useCredit ? $date->copy()->addDays((int) ($client->credit_days ?: 15))->toDateString() : null,
                'payment_type' => $useCredit ? 'credit' : 'cash',
                'tax_included' => true,
                'tax_rate' => $taxRate,
                'status' => 'completed',
                'notes' => 'Venta de demostración para mostrar a clientes',
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);
            $sale->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
            ])->save();

            $subtotalExcl = 0.0;
            $taxTotal = 0.0;
            $lineCount = 1 + ($index % 4);
            $lines = collect($products->random(min($lineCount, $products->count())));
            foreach ($lines as $product) {
                $available = $warehouse
                    ? $product->stockInWarehouse($warehouse->id)
                    : (float) $product->stock;
                if ($available < 1) {
                    continue;
                }

                $quantity = min(mt_rand(1, 4), (int) floor($available));
                if ($quantity < 1) {
                    continue;
                }

                $price = (float) $product->sale_price;
                $lineGross = round($quantity * $price, 2);
                $lineNet = $taxRate > 0 ? round($lineGross / (1 + $taxRate), 2) : $lineGross;
                $lineTax = round($lineGross - $lineNet, 2);
                $subtotalExcl += $lineNet;
                $taxTotal += $lineTax;

                SaleDetail::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'unit_id' => $product->base_unit_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineGross,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                ]);

                $inventory->stockOut(
                    $product->fresh(),
                    (float) $quantity,
                    'sale:'.$sale->id,
                    'Salida por factura '.$invoiceNumber,
                    $user->id,
                    false,
                    $warehouse?->id,
                );
            }

            if ($subtotalExcl <= 0) {
                $sale->delete();

                continue;
            }

            $total = round($subtotalExcl + $taxTotal, 2);
            $sale->update([
                'subtotal' => round($subtotalExcl, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => $total,
                'amount_paid' => $useCredit ? 0 : $total,
            ]);

            if ($useCredit && ($index % 2 === 0) && $total > 1) {
                $paidAmount = round($total * ((20 + ($index % 40)) / 100), 2);
                CreditPayment::query()->create([
                    'client_id' => $client->id,
                    'sale_id' => $sale->id,
                    'amount' => max(1, $paidAmount),
                    'payment_date' => $date->copy()->addDays(mt_rand(1, 8)),
                    'payment_type' => ['cash', 'transfer', 'cash'][$index % 3],
                    'reference_number' => 'ABO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'notes' => 'Abono de demostración',
                    'user_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, int>  $unitMap
     */
    private function unitId(string $unit, $unitMap): ?int
    {
        $abbrev = strtolower(trim($unit));
        $key = match (true) {
            in_array($abbrev, ['kg', 'kilo'], true) => 'kg',
            in_array($abbrev, ['lt', 'l', 'litro'], true) => 'lt',
            in_array($abbrev, ['gal', 'galon', 'galón'], true) => 'gal',
            in_array($abbrev, ['m', 'mt', 'metro'], true) => 'm',
            in_array($abbrev, ['saco', 'sacos'], true) => 'saco',
            in_array($abbrev, ['bolsa'], true) => 'bolsa',
            in_array($abbrev, ['var', 'varilla'], true) => 'var',
            in_array($abbrev, ['pln', 'plancha'], true) => 'pln',
            in_array($abbrev, ['caja'], true) => 'caja',
            default => 'und',
        };

        return $unitMap[$key] ?? $unitMap['und'] ?? null;
    }
}

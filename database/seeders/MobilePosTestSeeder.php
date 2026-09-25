<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MobilePosTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = Category::query()->firstOrCreate(
                ['name' => 'Pruebas POS móvil'],
                ['description' => 'Catálogo controlado para pruebas de venta desde teléfonos y tabletas.']
            );

            $warehouses = Branch::query()
                ->where('is_active', true)
                ->whereNotNull('warehouse_id')
                ->with('warehouse:id,name')
                ->orderBy('id')
                ->get()
                ->pluck('warehouse')
                ->filter()
                ->unique('id')
                ->values();

            $products = [
                ['code' => 'TEST-MOV-001', 'name' => 'Agua purificada 600 ml', 'buy' => 10, 'sell' => 15, 'unit' => 'unidad'],
                ['code' => 'TEST-MOV-002', 'name' => 'Gaseosa lata 355 ml', 'buy' => 18, 'sell' => 25, 'unit' => 'unidad'],
                ['code' => 'TEST-MOV-003', 'name' => 'Jugo de naranja 500 ml', 'buy' => 20, 'sell' => 30, 'unit' => 'unidad'],
                ['code' => 'TEST-MOV-004', 'name' => 'Galleta de avena', 'buy' => 12, 'sell' => 18, 'unit' => 'paquete'],
                ['code' => 'TEST-MOV-005', 'name' => 'Café molido 250 g', 'buy' => 75, 'sell' => 95, 'unit' => 'bolsa'],
                ['code' => 'TEST-MOV-006', 'name' => 'Azúcar 1 libra', 'buy' => 16, 'sell' => 22, 'unit' => 'libra'],
                ['code' => 'TEST-MOV-007', 'name' => 'Arroz 1 libra', 'buy' => 18, 'sell' => 24, 'unit' => 'libra'],
                ['code' => 'TEST-MOV-008', 'name' => 'Aceite vegetal 1 litro', 'buy' => 58, 'sell' => 72, 'unit' => 'litro'],
                ['code' => 'TEST-MOV-009', 'name' => 'Jabón de lavar', 'buy' => 15, 'sell' => 22, 'unit' => 'unidad'],
                ['code' => 'TEST-MOV-010', 'name' => 'Detergente 500 g', 'buy' => 35, 'sell' => 48, 'unit' => 'bolsa'],
                ['code' => 'TEST-MOV-011', 'name' => 'Papel higiénico 4 rollos', 'buy' => 42, 'sell' => 58, 'unit' => 'paquete'],
                ['code' => 'TEST-MOV-012', 'name' => 'Fósforos caja pequeña', 'buy' => 3, 'sell' => 5, 'unit' => 'unidad'],
            ];

            foreach ($products as $index => $data) {
                $quantityPerWarehouse = 30 + ($index * 2);
                $product = Product::query()->updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'category_id' => $category->id,
                        'name' => $data['name'],
                        'description' => 'Dato de prueba para verificar el flujo de venta móvil.',
                        'purchase_price' => $data['buy'],
                        'sale_price' => $data['sell'],
                        'stock' => $quantityPerWarehouse * $warehouses->count(),
                        'unit' => $data['unit'],
                        'location' => 'MÓVIL-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'low_stock_threshold' => 5,
                        'status' => 'active',
                    ]
                );

                foreach ($warehouses as $warehouse) {
                    WarehouseStock::query()->updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'quantity' => $quantityPerWarehouse,
                            'purchase_price' => $data['buy'],
                            'aisle' => 'TEST-MOV',
                        ]
                    );
                }
            }

            $clients = [
                ['code' => 'TEST-MOV-CLI-01', 'name' => 'Cliente contado móvil', 'phone' => '8888-0101', 'credit_enabled' => false, 'credit_limit' => 0],
                ['code' => 'TEST-MOV-CLI-02', 'name' => 'Cliente crédito móvil', 'phone' => '8888-0102', 'credit_enabled' => true, 'credit_limit' => 5000],
                ['code' => 'TEST-MOV-CLI-03', 'name' => 'Cliente frecuente móvil', 'phone' => '8888-0103', 'credit_enabled' => true, 'credit_limit' => 10000],
            ];

            foreach ($clients as $data) {
                Client::query()->updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'client_type' => Client::TYPE_NATURAL,
                        'phone' => $data['phone'],
                        'address' => 'Estelí, Nicaragua',
                        'department' => 'Estelí',
                        'municipality' => 'Estelí',
                        'status' => 'active',
                        'credit_enabled' => $data['credit_enabled'],
                        'credit_limit' => $data['credit_limit'],
                        'credit_days' => 30,
                    ]
                );
            }
        });
    }
}

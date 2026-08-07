<?php

namespace Database\Seeders;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Unidad', 'abbreviation' => 'und', 'unit_type' => 'count'],
            ['name' => 'Pieza', 'abbreviation' => 'pza', 'unit_type' => 'count'],
            ['name' => 'Kilogramo', 'abbreviation' => 'kg', 'unit_type' => 'weight'],
            ['name' => 'Libra', 'abbreviation' => 'lb', 'unit_type' => 'weight'],
            ['name' => 'Quintal', 'abbreviation' => 'qq', 'unit_type' => 'weight'],
            ['name' => 'Litro', 'abbreviation' => 'lt', 'unit_type' => 'volume'],
            ['name' => 'Galón', 'abbreviation' => 'gal', 'unit_type' => 'volume'],
            ['name' => 'Metro cúbico', 'abbreviation' => 'm3', 'unit_type' => 'volume'],
            ['name' => 'Metro', 'abbreviation' => 'm', 'unit_type' => 'length'],
            ['name' => 'Metro cuadrado', 'abbreviation' => 'm2', 'unit_type' => 'area'],
            ['name' => 'Saco', 'abbreviation' => 'saco', 'unit_type' => 'package'],
            ['name' => 'Bolsa', 'abbreviation' => 'bolsa', 'unit_type' => 'package'],
            ['name' => 'Varilla', 'abbreviation' => 'var', 'unit_type' => 'length'],
            ['name' => 'Plancha', 'abbreviation' => 'pln', 'unit_type' => 'count'],
            ['name' => 'Caja', 'abbreviation' => 'caja', 'unit_type' => 'package'],
        ];

        foreach ($units as $unit) {
            Unit::query()->firstOrCreate(['abbreviation' => $unit['abbreviation']], $unit);
        }

        $warehouses = [
            ['code' => 'BOD-01', 'name' => 'Bodega Principal', 'city' => 'Estelí', 'is_default' => true],
            ['code' => 'BOD-02', 'name' => 'Bodega Materiales', 'city' => 'Estelí', 'is_default' => false],
            ['code' => 'BOD-03', 'name' => 'Patio Ferretería', 'city' => 'Estelí', 'is_default' => false],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::query()->firstOrCreate(['code' => $warehouse['code']], array_merge($warehouse, [
                'is_active' => true,
                'address' => 'Estelí, Nicaragua',
            ]));
        }

        $defaultList = PriceList::query()->firstOrCreate(
            ['code' => 'GENERAL'],
            [
                'name' => 'Lista General Ferretería',
                'description' => 'Precios al público en córdobas',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        PriceList::query()->where('id', '!=', $defaultList->id)->update(['is_default' => false]);

        $wholesale = PriceList::query()->firstOrCreate(
            ['code' => 'MAYOR'],
            [
                'name' => 'Lista Mayorista',
                'description' => 'Precios para constructores y clientes frecuentes',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        unset($wholesale);

        $defaultWarehouse = Warehouse::default();
        $unitMap = Unit::query()->pluck('id', 'abbreviation');

        Product::query()->chunkById(100, function ($products) use ($defaultList, $defaultWarehouse, $unitMap) {
            foreach ($products as $product) {
                $abbrev = strtolower(trim((string) $product->unit));
                $baseUnitId = match (true) {
                    in_array($abbrev, ['kg', 'kilo', 'kilogramo'], true) => $unitMap['kg'] ?? null,
                    in_array($abbrev, ['lb', 'libra'], true) => $unitMap['lb'] ?? null,
                    in_array($abbrev, ['lt', 'l', 'litro', 'litros'], true) => $unitMap['lt'] ?? null,
                    in_array($abbrev, ['gal', 'galon', 'galón'], true) => $unitMap['gal'] ?? null,
                    in_array($abbrev, ['m3', 'm³', 'metro cubico'], true) => $unitMap['m3'] ?? null,
                    in_array($abbrev, ['m', 'mt', 'metro'], true) => $unitMap['m'] ?? null,
                    in_array($abbrev, ['m2', 'm²'], true) => $unitMap['m2'] ?? null,
                    in_array($abbrev, ['saco', 'sacos'], true) => $unitMap['saco'] ?? null,
                    in_array($abbrev, ['bolsa'], true) => $unitMap['bolsa'] ?? null,
                    in_array($abbrev, ['var', 'varilla'], true) => $unitMap['var'] ?? null,
                    default => $unitMap['und'] ?? null,
                };

                if ($baseUnitId && ! $product->base_unit_id) {
                    $product->update(['base_unit_id' => $baseUnitId]);
                }

                PriceListItem::query()->updateOrCreate(
                    [
                        'price_list_id' => $defaultList->id,
                        'product_id' => $product->id,
                        'unit_id' => $product->base_unit_id,
                    ],
                    [
                        'unit_price' => $product->sale_price,
                        'min_quantity' => 1,
                    ]
                );

                if ($defaultWarehouse && (float) $product->stock > 0) {
                    WarehouseStock::query()->updateOrCreate(
                        [
                            'warehouse_id' => $defaultWarehouse->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'quantity' => $product->stock,
                            'aisle' => $product->location,
                        ]
                    );
                }
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }
    }
}

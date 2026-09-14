<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $items = [
            ['category' => 'Fertilizantes', 'name' => 'Fertilizante Nitrogenado', 'code' => 'F001', 'description' => 'Fertilizante NPK', 'purchase_price' => 10.50, 'sale_price' => 15.00, 'stock' => 50, 'unit' => 'kg'],
            ['category' => 'Semillas', 'name' => 'Semilla Maíz', 'code' => 'S001', 'description' => 'Maíz híbrido', 'purchase_price' => 2.00, 'sale_price' => 3.50, 'stock' => 100, 'unit' => 'kg'],
            ['category' => 'Plaguicidas', 'name' => 'Plaguicida ABC', 'code' => 'P001', 'description' => 'Control de insectos', 'purchase_price' => 12.00, 'sale_price' => 18.00, 'stock' => 30, 'unit' => 'lt'],
            ['category' => 'Herramientas', 'name' => 'Azadón', 'code' => 'H001', 'description' => 'Herramienta de jardín', 'purchase_price' => 5.00, 'sale_price' => 8.00, 'stock' => 20, 'unit' => 'unidad'],
        ];

        foreach ($items as $it) {
            $catId = $categories[$it['category']] ?? null;
            if (! $catId) {
                continue;
            }

            Product::updateOrCreate(
                ['code' => $it['code']],
                [
                    'category_id' => $catId,
                    'name' => $it['name'],
                    'description' => $it['description'],
                    'purchase_price' => $it['purchase_price'],
                    'sale_price' => $it['sale_price'],
                    'stock' => $it['stock'],
                    'unit' => $it['unit'],
                ]
            );
        }
    }
}
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

        Product::insert([
            ['category_id' => $categories['Fertilizantes'], 'name' => 'Fertilizante Nitrogenado', 'code' => 'F001', 'description' => 'Fertilizante NPK', 'purchase_price' => 10.50, 'sale_price' => 15.00, 'stock' => 50, 'unit' => 'kg'],
            ['category_id' => $categories['Semillas'], 'name' => 'Semilla Maíz', 'code' => 'S001', 'description' => 'Maíz híbrido', 'purchase_price' => 2.00, 'sale_price' => 3.50, 'stock' => 100, 'unit' => 'kg'],
            ['category_id' => $categories['Plaguicidas'], 'name' => 'Plaguicida ABC', 'code' => 'P001', 'description' => 'Control de insectos', 'purchase_price' => 12.00, 'sale_price' => 18.00, 'stock' => 30, 'unit' => 'lt'],
            ['category_id' => $categories['Herramientas'], 'name' => 'Azadón', 'code' => 'H001', 'description' => 'Herramienta de jardín', 'purchase_price' => 5.00, 'sale_price' => 8.00, 'stock' => 20, 'unit' => 'unidad'],
        ]);
    }
}
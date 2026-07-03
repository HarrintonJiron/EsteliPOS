<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::insert([
            ['name' => 'Fertilizantes', 'description' => 'Productos para fertilizar'],
            ['name' => 'Semillas', 'description' => 'Semillas de todo tipo'],
            ['name' => 'Plaguicidas', 'description' => 'Control de plagas'],
            ['name' => 'Herramientas', 'description' => 'Herramientas agrícolas'],
        ]);
    }
}
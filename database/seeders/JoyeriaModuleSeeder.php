<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JoyeriaModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Activar el módulo de joyería
        DB::table('modules')->where('slug', 'joyeria')->update(['is_active' => true]);
        
        echo "✓ Módulo de joyería activado en la base de datos\n";
    }
}

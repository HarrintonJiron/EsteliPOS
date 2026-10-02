<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_brands', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('workshop_type', 20)->default('repair')->after('name')->index();
            $table->unique(['workshop_type', 'name']);
        });

        Schema::table('repair_services', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('workshop_type', 20)->default('repair')->after('name')->index();
            $table->unique(['workshop_type', 'name']);
        });

        $now = now();
        $types = [
            'Anillo', 'Argolla', 'Aretes', 'Brazalete', 'Cadena', 'Dije',
            'Esclava', 'Pulsera', 'Reloj', 'Rosario', 'Tobillera', 'Otra joya',
        ];

        foreach ($types as $name) {
            DB::table('device_brands')->updateOrInsert(
                ['workshop_type' => 'jewelry', 'name' => $name],
                ['workshop_type' => 'jewelry', 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $services = [
            ['Limpieza y pulido', 200],
            ['Soldadura', 300],
            ['Ajuste de talla', 350],
            ['Engaste de piedra', 300],
            ['Reposición de piedra', 500],
            ['Cambio de broche', 250],
            ['Cambio de argolla', 200],
            ['Baño de oro', 600],
            ['Rodinado', 600],
            ['Grabado', 250],
            ['Enderezado', 250],
            ['Evaluación del joyero', 100],
        ];

        foreach ($services as [$name, $price]) {
            DB::table('repair_services')->updateOrInsert(
                ['workshop_type' => 'jewelry', 'name' => $name],
                [
                    'workshop_type' => 'jewelry',
                    'description' => null,
                    'price' => $price,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('repair_services')->where('workshop_type', 'jewelry')->delete();
        DB::table('device_brands')->where('workshop_type', 'jewelry')->delete();

        Schema::table('repair_services', function (Blueprint $table) {
            $table->dropUnique(['workshop_type', 'name']);
            $table->dropColumn('workshop_type');
            $table->unique('name');
        });

        Schema::table('device_brands', function (Blueprint $table) {
            $table->dropUnique(['workshop_type', 'name']);
            $table->dropColumn('workshop_type');
            $table->unique('name');
        });
    }
};

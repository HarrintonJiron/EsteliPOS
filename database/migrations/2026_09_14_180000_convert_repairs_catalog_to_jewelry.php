<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PHONE_BRANDS = [
        'Samsung', 'Apple', 'Xiaomi', 'Huawei', 'Motorola', 'LG', 'Sony',
        'Nokia', 'OPPO', 'Realme', 'OnePlus', 'Tecno', 'ZTE',
    ];

    private const PHONE_SERVICES = [
        'Cambio de Pantalla', 'Cambio de Batería', 'Formateo de Software',
        'Limpieza de Puertos de Carga', 'Cambio de Conector de Carga',
        'Reparación de Altavoz', 'Cambio de Micrófono', 'Reparación de Cámara',
        'Diagnóstico Técnico', 'Desbloqueo de Contraseña',
        'Cambio de Sensor de Huella', 'Cambio de Face ID',
    ];

    private const JEWELRY_TYPES = [
        'Anillo', 'Argolla', 'Aretes', 'Brazalete', 'Cadena', 'Dije',
        'Esclava', 'Pulsera', 'Reloj', 'Rosario', 'Tobillera', 'Otra joya',
    ];

    private const JEWELRY_SERVICES = [
        ['Limpieza y pulido', 'Limpieza profesional y recuperación de brillo', 200],
        ['Soldadura', 'Unión o reparación de pieza quebrada', 300],
        ['Ajuste de talla', 'Ampliación o reducción de talla de anillo', 350],
        ['Engaste de piedra', 'Ajuste o colocación de piedra', 300],
        ['Reposición de piedra', 'Suministro y colocación de piedra', 500],
        ['Cambio de broche', 'Reemplazo de broche en cadena o pulsera', 250],
        ['Cambio de argolla', 'Reemplazo de argolla o unión', 200],
        ['Baño de oro', 'Recubrimiento y acabado en baño de oro', 600],
        ['Rodinado', 'Aplicación de rodio y acabado', 600],
        ['Grabado', 'Grabado personalizado en la pieza', 250],
        ['Enderezado', 'Corrección de deformaciones de la joya', 250],
        ['Evaluación del joyero', 'Inspección de material, piedras, uniones y estado', 100],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('device_brands')->whereIn('name', self::PHONE_BRANDS)->update(['is_active' => false]);
            DB::table('repair_services')->whereIn('name', self::PHONE_SERVICES)->update(['is_active' => false]);

            foreach (self::JEWELRY_TYPES as $type) {
                DB::table('device_brands')->updateOrInsert(
                    ['name' => $type],
                    ['is_active' => true, 'updated_at' => now(), 'created_at' => now()],
                );
            }

            foreach (self::JEWELRY_SERVICES as [$name, $description, $price]) {
                DB::table('repair_services')->updateOrInsert(
                    ['name' => $name],
                    [
                        'description' => $description,
                        'price' => $price,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            DB::table('modules')->where('slug', 'reparaciones')->update([
                'name' => 'Taller de Joyería',
                'description' => 'Recepción, reparación y entrega de joyas',
            ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('device_brands')->whereIn('name', self::JEWELRY_TYPES)->update(['is_active' => false]);
            DB::table('repair_services')->whereIn(
                'name',
                array_column(self::JEWELRY_SERVICES, 0),
            )->update(['is_active' => false]);
            DB::table('device_brands')->whereIn('name', self::PHONE_BRANDS)->update(['is_active' => true]);
            DB::table('repair_services')->whereIn('name', self::PHONE_SERVICES)->update(['is_active' => true]);
            DB::table('modules')->where('slug', 'reparaciones')->update([
                'name' => 'Reparaciones',
                'description' => 'Órdenes y seguimiento de reparaciones',
            ]);
        });
    }
};

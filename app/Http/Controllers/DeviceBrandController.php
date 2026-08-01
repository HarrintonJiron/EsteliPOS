<?php

namespace App\Http\Controllers;

use App\Models\DeviceBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DeviceBrandController extends Controller
{
    private function ensureTableExists()
    {
        if (! Schema::hasTable('device_brands')) {
            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_08_01_090123_create_device_brands_table.php',
                    '--force' => true,
                ]);

                // Run seeder after migration
                Artisan::call('db:seed', [
                    '--class' => 'DeviceBrandSeeder',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Failed to create device_brands table: '.$e->getMessage());
            }
        }
    }

    public function index()
    {
        $this->ensureTableExists();

        try {
            $brands = DeviceBrand::active()->orderBy('name')->get(['id', 'name']);

            return response()->json($brands);
        } catch (\Exception $e) {
            // Return default brands if something goes wrong
            $defaultBrands = [
                ['id' => 1, 'name' => 'Samsung'],
                ['id' => 2, 'name' => 'Apple'],
                ['id' => 3, 'name' => 'Xiaomi'],
                ['id' => 4, 'name' => 'Huawei'],
                ['id' => 5, 'name' => 'Motorola'],
                ['id' => 6, 'name' => 'LG'],
                ['id' => 7, 'name' => 'Sony'],
                ['id' => 8, 'name' => 'Nokia'],
                ['id' => 9, 'name' => 'OPPO'],
                ['id' => 10, 'name' => 'Realme'],
                ['id' => 11, 'name' => 'OnePlus'],
                ['id' => 12, 'name' => 'Tecno'],
                ['id' => 13, 'name' => 'ZTE'],
            ];

            return response()->json($defaultBrands);
        }
    }

    public function store(Request $request)
    {
        $this->ensureTableExists();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:device_brands,name',
            ]);

            $brand = DeviceBrand::create([
                'name' => $validated['name'],
                'is_active' => true,
            ]);

            return response()->json($brand, 201);
        } catch (\Exception $e) {
            // If validation fails due to duplicate, return error
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json(['error' => 'Esta marca ya existe'], 422);
            }
            throw $e;
        }
    }
}

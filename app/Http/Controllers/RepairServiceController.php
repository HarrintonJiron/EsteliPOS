<?php

namespace App\Http\Controllers;

use App\Models\RepairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class RepairServiceController extends Controller
{
    private function ensureTableExists()
    {
        if (! Schema::hasTable('repair_services')) {
            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_08_01_100154_create_repair_services_table.php',
                    '--force' => true,
                ]);

                Artisan::call('db:seed', [
                    '--class' => 'RepairServiceSeeder',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create repair_services table: '.$e->getMessage());
            }
        }
    }

    public function index()
    {
        $this->ensureTableExists();

        try {
            $services = RepairService::active()->orderBy('name')->get(['id', 'name', 'description', 'price']);

            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function store(Request $request)
    {
        $this->ensureTableExists();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|unique:repair_services,name',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $service = RepairService::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'is_active' => true,
            ]);

            return response()->json($service, 201);
        } catch (\Exception $e) {
            \Log::error('Error creating repair service: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al crear el servicio en la base de datos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $service = RepairService::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:200|unique:repair_services,name,'.$id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy($id)
    {
        $service = RepairService::findOrFail($id);
        $service->delete();

        return response()->json(null, 204);
    }
}

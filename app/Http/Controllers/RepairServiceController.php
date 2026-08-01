<?php

namespace App\Http\Controllers;

use App\Models\RepairService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RepairServiceController extends Controller
{
    public function index()
    {
        $services = RepairService::active()->orderBy('name')->get(['id', 'name', 'description', 'price']);

        return response()->json($services);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|unique:repair_services,name',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
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

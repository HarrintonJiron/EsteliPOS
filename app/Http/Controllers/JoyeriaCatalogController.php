<?php

namespace App\Http\Controllers;

use App\Models\DeviceBrand;
use App\Models\RepairService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JoyeriaCatalogController extends Controller
{
    public function types()
    {
        return response()->json(
            DeviceBrand::forWorkshop('jewelry')->active()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('device_brands', 'name')->where('workshop_type', 'jewelry')],
        ]);

        $type = DeviceBrand::create([
            'name' => $validated['name'],
            'workshop_type' => 'jewelry',
            'is_active' => true,
        ]);

        return response()->json($type, 201);
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200', Rule::unique('repair_services', 'name')->where('workshop_type', 'jewelry')],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $service = RepairService::create([
            ...$validated,
            'workshop_type' => 'jewelry',
            'is_active' => true,
        ]);

        return response()->json($service, 201);
    }
}

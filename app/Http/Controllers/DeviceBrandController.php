<?php

namespace App\Http\Controllers;

use App\Models\DeviceBrand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceBrandController extends Controller
{
    public function index()
    {
        return response()->json(DeviceBrand::forWorkshop('repair')->active()->orderBy('name')->get(['id', 'name']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('device_brands', 'name')->where('workshop_type', 'repair')],
        ]);

        $brand = DeviceBrand::create([
            'name' => $validated['name'],
            'workshop_type' => 'repair',
            'is_active' => true,
        ]);

        return response()->json($brand, 201);
    }
}

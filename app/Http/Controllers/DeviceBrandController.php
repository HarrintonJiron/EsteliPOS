<?php

namespace App\Http\Controllers;

use App\Models\DeviceBrand;
use Illuminate\Http\Request;

class DeviceBrandController extends Controller
{
    public function index()
    {
        $brands = DeviceBrand::active()->orderBy('name')->get(['id', 'name']);

        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:device_brands,name',
        ]);

        $brand = DeviceBrand::create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return response()->json($brand, 201);
    }
}

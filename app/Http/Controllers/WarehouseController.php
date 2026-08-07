<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->withCount(['stocks as products_count' => fn ($q) => $q->where('quantity', '>', 0)])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $stocks = WarehouseStock::query()
            ->with(['warehouse', 'product'])
            ->where('quantity', '>', 0)
            ->latest('updated_at')
            ->limit(15)
            ->get();

        return view('inventario.warehouses.index', compact('warehouses', 'stocks'));
    }

    public function create(): View
    {
        return view('inventario.warehouses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:120',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::query()->update(['is_default' => false]);
        }

        Warehouse::query()->create(array_merge($validated, [
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]));

        return redirect()->route('inventario.warehouses.index')->with('success', 'Bodega creada correctamente.');
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('inventario.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code,'.$warehouse->id,
            'name' => 'required|string|max:120',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::query()->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update([
            ...$validated,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventario.warehouses.index')->with('success', 'Bodega actualizada.');
    }

    public function show(Warehouse $warehouse): View
    {
        $stocks = WarehouseStock::query()
            ->with(['product.category', 'product.baseUnit'])
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->paginate(25);

        $totalValue = (float) WarehouseStock::query()
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->where('warehouse_stocks.warehouse_id', $warehouse->id)
            ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity * products.purchase_price), 0) as total')
            ->value('total');

        return view('inventario.warehouses.show', compact('warehouse', 'stocks', 'totalValue'));
    }
}

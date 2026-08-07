<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->withCount([
                'stocks as products_count' => fn ($q) => $q->where('quantity', '>', 0),
            ])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $warehouseIds = $warehouses->pluck('id');
        $values = WarehouseStock::query()
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->whereIn('warehouse_stocks.warehouse_id', $warehouseIds)
            ->where('warehouse_stocks.quantity', '>', 0)
            ->groupBy('warehouse_stocks.warehouse_id')
            ->selectRaw('warehouse_stocks.warehouse_id, COALESCE(SUM(warehouse_stocks.quantity * products.purchase_price), 0) as total_value')
            ->pluck('total_value', 'warehouse_id');

        $warehouses->each(function (Warehouse $warehouse) use ($values) {
            $warehouse->estimated_value = (float) ($values[$warehouse->id] ?? 0);
        });

        $stats = [
            'active' => $warehouses->where('is_active', true)->count(),
            'products_with_stock' => (int) $warehouses->sum('products_count'),
            'estimated_value' => (float) $warehouses->sum('estimated_value'),
        ];

        $recentStocks = WarehouseStock::query()
            ->with(['warehouse', 'product'])
            ->where('quantity', '>', 0)
            ->latest('updated_at')
            ->limit(12)
            ->get();

        return view('inventario.warehouses.index', compact('warehouses', 'stats', 'recentStocks'));
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
        ], [
            'code.required' => 'El código de bodega es obligatorio.',
            'code.unique' => 'Ya existe una bodega con ese código.',
            'name.required' => 'El nombre de la bodega es obligatorio.',
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

        if (! $request->boolean('is_active') && $warehouse->is_default) {
            return back()->with('error', 'No puedes desactivar la bodega principal. Primero marca otra como principal.');
        }

        $warehouse->update([
            ...$validated,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventario.warehouses.show', $warehouse)->with('success', 'Bodega actualizada.');
    }

    public function show(Request $request, Warehouse $warehouse): View
    {
        $search = trim((string) $request->get('q', ''));

        $stocks = WarehouseStock::query()
            ->with(['product.category', 'product.baseUnit'])
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('quantity')
            ->paginate(25)
            ->withQueryString();

        $totalValue = (float) WarehouseStock::query()
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->where('warehouse_stocks.warehouse_id', $warehouse->id)
            ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity * products.purchase_price), 0) as total')
            ->value('total');

        $productsCount = (int) WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->count();

        $otherWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->where('id', '!=', $warehouse->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $transferProducts = WarehouseStock::query()
            ->with('product:id,name,code,unit,base_unit_id')
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit(200)
            ->get();

        return view('inventario.warehouses.show', compact(
            'warehouse',
            'stocks',
            'totalValue',
            'productsCount',
            'search',
            'otherWarehouses',
            'transferProducts',
        ));
    }

    public function transfer(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.0001',
            'note' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Selecciona un producto.',
            'to_warehouse_id.required' => 'Selecciona la bodega destino.',
            'quantity.required' => 'Indica la cantidad a transferir.',
        ]);

        if ((int) $validated['to_warehouse_id'] === $warehouse->id) {
            return back()->with('error', 'La bodega destino debe ser distinta a la de origen.');
        }

        $product = Product::query()->findOrFail($validated['product_id']);

        try {
            $this->inventory->transfer(
                $product,
                (float) $validated['quantity'],
                $warehouse->id,
                (int) $validated['to_warehouse_id'],
                (string) ($validated['note'] ?? ''),
                $request->user()?->id,
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Transferencia realizada correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->is_default) {
            return back()->with('error', 'No se puede eliminar la bodega principal.');
        }

        $hasStock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->exists();

        if ($hasStock) {
            return back()->with('error', 'No se puede eliminar una bodega con existencias. Transfiere el stock primero.');
        }

        WarehouseStock::query()->where('warehouse_id', $warehouse->id)->delete();
        $warehouse->delete();

        return redirect()->route('inventario.warehouses.index')->with('success', 'Bodega eliminada.');
    }
}

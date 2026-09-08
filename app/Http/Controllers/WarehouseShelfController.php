<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseShelfController extends Controller
{
    public function index(Request $request): View
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $search = trim((string) $request->query('q', ''));
        $shelves = WarehouseShelf::query()
            ->with('warehouse:id,name,code')
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('warehouse_id')
            ->orderBy('code')
            ->paginate(24)
            ->withQueryString();

        $shelves->getCollection()->each(function (WarehouseShelf $shelf): void {
            $stockQuery = WarehouseStock::query()
                ->where('warehouse_id', $shelf->warehouse_id)
                ->where('aisle', $shelf->code);
            $shelf->products_count = (clone $stockQuery)->count();
            $shelf->total_quantity = (float) (clone $stockQuery)->sum('quantity');
        });

        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $stats = [
            'total' => WarehouseShelf::query()->count(),
            'active' => WarehouseShelf::query()->where('is_active', true)->count(),
            'with_products' => WarehouseStock::query()
                ->whereNotNull('aisle')
                ->select(['warehouse_id', 'aisle'])
                ->groupBy('warehouse_id', 'aisle')
                ->get()
                ->count(),
        ];

        return view('inventario.shelves.index', compact('shelves', 'warehouses', 'warehouseId', 'search', 'stats'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('inventario.shelves.create', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateShelf($request);
        $shelf = WarehouseShelf::query()->create([...$validated, 'is_active' => true]);

        return redirect()->route('inventario.shelves.show', $shelf)->with('success', 'Estante creado correctamente.');
    }

    public function show(WarehouseShelf $shelf): View
    {
        $shelf->load('warehouse');
        $stocks = WarehouseStock::query()
            ->with(['product.category', 'product.baseUnit'])
            ->where('warehouse_id', $shelf->warehouse_id)
            ->where('aisle', $shelf->code)
            ->orderByDesc('quantity')
            ->paginate(25);
        $totalQuantity = (float) WarehouseStock::query()
            ->where('warehouse_id', $shelf->warehouse_id)
            ->where('aisle', $shelf->code)
            ->sum('quantity');

        return view('inventario.shelves.show', compact('shelf', 'stocks', 'totalQuantity'));
    }

    public function edit(WarehouseShelf $shelf): View
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('inventario.shelves.edit', compact('shelf', 'warehouses'));
    }

    public function update(Request $request, WarehouseShelf $shelf): RedirectResponse
    {
        $validated = $this->validateShelf($request, $shelf);
        $oldWarehouseId = $shelf->warehouse_id;
        $oldCode = $shelf->code;

        if ((int) $validated['warehouse_id'] !== $oldWarehouseId && WarehouseStock::query()
            ->where('warehouse_id', $oldWarehouseId)
            ->where('aisle', $oldCode)
            ->exists()) {
            return back()->withInput()->with('error', 'No puedes cambiar de bodega un estante que ya tiene productos asignados.');
        }

        DB::transaction(function () use ($shelf, $validated, $oldWarehouseId, $oldCode): void {
            $shelf->update($validated);
            WarehouseStock::query()
                ->where('warehouse_id', $oldWarehouseId)
                ->where('aisle', $oldCode)
                ->update(['warehouse_id' => $shelf->warehouse_id, 'aisle' => $shelf->code]);
        });

        return redirect()->route('inventario.shelves.show', $shelf)->with('success', 'Estante actualizado correctamente.');
    }

    private function validateShelf(Request $request, ?WarehouseShelf $shelf = null): array
    {
        $warehouseId = $request->integer('warehouse_id');

        return $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'code' => 'required|string|max:50|unique:warehouse_shelves,code,'.($shelf?->id ?? 'NULL').',id,warehouse_id,'.$warehouseId,
            'name' => 'nullable|string|max:120',
            'is_active' => 'sometimes|boolean',
        ], [
            'warehouse_id.required' => 'Selecciona una bodega.',
            'code.required' => 'El código del estante es obligatorio.',
            'code.unique' => 'Ese estante ya existe en la bodega seleccionada.',
        ]);
    }
}

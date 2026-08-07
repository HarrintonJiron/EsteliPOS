<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjusteInventarioController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private PosCatalogService $posCatalog,
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = InventoryAdjustment::with(['product', 'user'])->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->paginate($perPage);
        $products = Product::orderBy('name')->get();

        $stats = [
            'total_adjustments' => InventoryAdjustment::count(),
            'total_increases' => InventoryAdjustment::where('type', 'increase')->sum('quantity'),
            'total_decreases' => InventoryAdjustment::where('type', 'decrease')->sum('quantity'),
            'total_count_adjustments' => InventoryAdjustment::where('type', 'count')->count(),
        ];

        return view('ajustes.index', compact('adjustments', 'products', 'stats'));
    }

    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $preselectedProduct = null;

        if ($request->filled('product_id')) {
            $preselectedProduct = Product::find($request->product_id);
        }

        return view('ajustes.create', compact('products', 'preselectedProduct', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'type' => 'required|in:increase,decrease,count',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        $warehouseId = $this->posCatalog->resolveWarehouseId($validated['warehouse_id'] ?? null);

        try {
            DB::transaction(function () use ($validated, $request, $warehouseId) {
                $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
                $stockBefore = (float) $product->stock;
                $quantity = (float) $validated['quantity'];

                $adjustment = InventoryAdjustment::create([
                    'product_id' => $validated['product_id'],
                    'warehouse_id' => $warehouseId,
                    'user_id' => $request->user()?->id ?? 1,
                    'type' => $validated['type'],
                    'quantity' => 0,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore,
                    'reason' => $validated['reason'],
                    'reference' => $validated['reference'] ?? null,
                ]);

                $reference = 'adjustment:'.$adjustment->id;
                $adjustmentQuantity = 0.0;

                switch ($validated['type']) {
                    case 'increase':
                        $adjustmentQuantity = $quantity;
                        $this->inventoryService->stockIn(
                            $product,
                            $quantity,
                            $reference,
                            $validated['reason'],
                            $request->user()?->id,
                            $warehouseId,
                        );
                        break;
                    case 'decrease':
                        $adjustmentQuantity = -$quantity;
                        $this->inventoryService->stockOut(
                            $product,
                            $quantity,
                            $reference,
                            $validated['reason'],
                            $request->user()?->id,
                            false,
                            $warehouseId,
                        );
                        break;
                    case 'count':
                        $currentWarehouseQty = $product->stockInWarehouse($warehouseId);
                        $delta = $quantity - $currentWarehouseQty;
                        $adjustmentQuantity = $delta;
                        if ($delta > 0) {
                            $this->inventoryService->stockIn(
                                $product,
                                $delta,
                                $reference,
                                'Conteo físico: '.$validated['reason'],
                                $request->user()?->id,
                                $warehouseId,
                            );
                        } elseif ($delta < 0) {
                            $this->inventoryService->stockOut(
                                $product,
                                abs($delta),
                                $reference,
                                'Conteo físico: '.$validated['reason'],
                                $request->user()?->id,
                                false,
                                $warehouseId,
                            );
                        }
                        break;
                }

                $product->refresh();
                $adjustment->update([
                    'quantity' => $adjustmentQuantity,
                    'stock_after' => (float) $product->stock,
                ]);

                $this->accountingService->recordInventoryAdjustment($adjustment->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('ajustes.index')->with('success', 'Ajuste de inventario registrado correctamente.');
    }

    public function show($id)
    {
        $adjustment = InventoryAdjustment::with(['product', 'user'])->findOrFail($id);

        return view('ajustes.show', compact('adjustment'));
    }

    public function destroy($id)
    {
        $adjustment = InventoryAdjustment::findOrFail($id);

        try {
            DB::transaction(function () use ($adjustment) {
                $product = Product::query()->lockForUpdate()->findOrFail($adjustment->product_id);
                $warehouseId = $adjustment->warehouse_id ?? $this->posCatalog->resolveWarehouseId(null);

                if (abs((float) $product->stock - (float) $adjustment->stock_after) > 0.0001) {
                    throw new \RuntimeException(
                        'No se puede eliminar este ajuste porque el producto ya tuvo movimientos posteriores.'
                    );
                }

                $qty = abs((float) $adjustment->quantity);
                if ((float) $adjustment->quantity >= 0) {
                    $this->inventoryService->stockOut(
                        $product,
                        $qty,
                        'adjustment_revert:'.$adjustment->id,
                        'Reverso de ajuste #'.$adjustment->id,
                        auth()->id(),
                        false,
                        $warehouseId,
                    );
                } elseif ($qty > 0) {
                    $this->inventoryService->stockIn(
                        $product,
                        $qty,
                        'adjustment_revert:'.$adjustment->id,
                        'Reverso de ajuste #'.$adjustment->id,
                        auth()->id(),
                        $warehouseId,
                    );
                }

                $adjustmentId = $adjustment->id;
                $adjustment->delete();

                $this->accountingService->voidForSource(InventoryAdjustment::class, $adjustmentId, 'Ajuste eliminado');
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ajustes.index')->with('success', 'Ajuste eliminado y stock restaurado.');
    }

    public function getProductInfo($id)
    {
        $product = Product::with('baseUnit')->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'current_stock' => $product->stock,
            'unit' => $product->baseUnitLabel(),
            'lot' => $product->lot,
            'expiry_date' => $product->expiry_date?->format('d/m/Y'),
            'location' => $product->location,
            'status' => $product->status,
            'status_label' => $product->status_label,
        ]);
    }
}

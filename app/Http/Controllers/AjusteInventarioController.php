<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjusteInventarioController extends Controller
{
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
        $preselectedProduct = null;

        if ($request->filled('product_id')) {
            $preselectedProduct = Product::find($request->product_id);
        }

        return view('ajustes.create', compact('products', 'preselectedProduct'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:increase,decrease,count',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|min:5|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::findOrFail($validated['product_id']);
            $stockBefore = $product->stock;

            $quantity = $validated['quantity'];
            $adjustmentQuantity = 0;
            $stockAfter = $stockBefore;

            switch ($validated['type']) {
                case 'increase':
                    $adjustmentQuantity = $quantity;
                    $stockAfter = $stockBefore + $quantity;
                    break;
                case 'decrease':
                    $adjustmentQuantity = -$quantity;
                    $stockAfter = max(0, $stockBefore - $quantity);
                    break;
                case 'count':
                    $adjustmentQuantity = $quantity - $stockBefore;
                    $stockAfter = $quantity;
                    break;
            }

            $product->update(['stock' => $stockAfter]);

            $adjustment = InventoryAdjustment::create([
                'product_id' => $validated['product_id'],
                'user_id' => $request->user()?->id ?? 1,
                'type' => $validated['type'],
                'quantity' => $adjustmentQuantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $validated['reason'],
                'reference' => $validated['reference'] ?? null,
            ]);

            InventoryMovement::create([
                'product_id' => $validated['product_id'],
                'type' => $adjustmentQuantity >= 0 ? 'in' : 'out',
                'quantity' => abs($adjustmentQuantity),
                'stock_after' => $stockAfter,
                'reference' => 'adjustment:' . $adjustment->id,
                'note' => 'Ajuste: ' . $validated['reason'],
                'user_id' => $request->user()?->id ?? 1,
            ]);
        });

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

        DB::transaction(function () use ($adjustment) {
            $product = Product::findOrFail($adjustment->product_id);

            $product->update(['stock' => $adjustment->stock_before]);

            InventoryMovement::create([
                'product_id' => $adjustment->product_id,
                'type' => $adjustment->quantity >= 0 ? 'out' : 'in',
                'quantity' => abs($adjustment->quantity),
                'stock_after' => $adjustment->stock_before,
                'reference' => 'adjustment_revert:' . $adjustment->id,
                'note' => 'Reverso de ajuste #' . $adjustment->id,
                'user_id' => auth()->id() ?? 1,
            ]);

            $adjustment->delete();
        });

        return redirect()->route('ajustes.index')->with('success', 'Ajuste eliminado y stock restaurado.');
    }

    public function getProductInfo($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'current_stock' => $product->stock,
            'unit' => $product->unit,
            'lot' => $product->lot,
            'expiry_date' => $product->expiry_date?->format('d/m/Y'),
            'location' => $product->location,
            'status' => $product->status,
            'status_label' => $product->status_label,
        ]);
    }
}

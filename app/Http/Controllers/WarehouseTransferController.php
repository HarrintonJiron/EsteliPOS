<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseTransferController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('q', ''));

        $outgoing = InventoryMovement::query()
            ->with(['product:id,name,code', 'warehouse:id,name,code', 'user:id,name'])
            ->where('type', 'out')
            ->where('reference', 'like', 'transfer:%')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('note', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('code', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $references = $outgoing->getCollection()->pluck('reference')->filter()->unique()->values();
        $incomingByReference = InventoryMovement::query()
            ->with('warehouse:id,name,code')
            ->where('type', 'in')
            ->whereIn('reference', $references)
            ->get()
            ->keyBy('reference');

        $transfers = $outgoing->through(function (InventoryMovement $out) use ($incomingByReference) {
            $in = $incomingByReference->get($out->reference);

            return [
                'reference' => $out->reference,
                'product' => $out->product,
                'from' => $out->warehouse,
                'to' => $in?->warehouse,
                'quantity' => (float) $out->quantity,
                'note' => $out->note,
                'user' => $out->user,
                'created_at' => $out->created_at,
            ];
        });

        return view('inventario.transfers.index', [
            'transfers' => $transfers,
            'search' => $search,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->limit(300)->get(['id', 'name', 'code', 'stock']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|numeric|min:0.0001',
            'note' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Selecciona un producto.',
            'from_warehouse_id.required' => 'Selecciona la bodega de origen.',
            'to_warehouse_id.required' => 'Selecciona la bodega de destino.',
            'to_warehouse_id.different' => 'Origen y destino deben ser distintas.',
            'quantity.required' => 'Indica la cantidad a transferir.',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        try {
            $this->inventory->transfer(
                $product,
                (float) $validated['quantity'],
                (int) $validated['from_warehouse_id'],
                (int) $validated['to_warehouse_id'],
                (string) ($validated['note'] ?? ''),
                $request->user()?->id,
            );
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventario.transfers.index')
            ->with('success', 'Transferencia interna registrada correctamente.');
    }

    public function stockAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $quantity = (float) (WarehouseStock::query()
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->value('quantity') ?? 0);

        return response()->json([
            'quantity' => $quantity,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private PosCatalogService $posCatalog,
        private PricingService $pricing,
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Purchase::with('supplier', 'user', 'warehouse');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate($perPage)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats = [
            'month_total' => (float) Purchase::query()
                ->where('status', 'completed')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('total'),
            'completed_count' => Purchase::query()->where('status', 'completed')->count(),
            'pending_count' => Purchase::query()->where('status', 'pending')->count(),
            'invested_total' => (float) Purchase::query()
                ->where('status', 'completed')
                ->sum('total'),
        ];

        return view('compras.index', compact('purchases', 'suppliers', 'stats'));
    }

    public function searchProducts(Request $request): JsonResponse
    {
        return response()->json($this->resolvePurchaseProductSearch(
            trim($request->string('search')->toString()),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
        ));
    }

    public function buscarProductos(Request $request, $supplierId): JsonResponse
    {
        return response()->json($this->resolvePurchaseProductSearch(
            trim($request->string('search')->toString()),
            (int) $supplierId,
        ));
    }

    /**
     * @return list<array{id: int, name: string, code: string, price: float, has_supplier_price: bool}>
     */
    private function resolvePurchaseProductSearch(string $search, ?int $supplierId): array
    {
        if (strlen($search) < 2) {
            return [];
        }

        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (Product $product) use ($supplierId) {
                $supplierPrice = null;

                if ($supplierId) {
                    $pivot = $product->suppliers()
                        ->where('supplier_id', $supplierId)
                        ->first()
                        ?->pivot;

                    if ($pivot?->purchase_price !== null) {
                        $supplierPrice = (float) $pivot->purchase_price;
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => $supplierPrice ?? (float) ($product->purchase_price ?? 0),
                    'has_supplier_price' => $supplierPrice !== null,
                ];
            })
            ->values()
            ->all();
    }

    public function nextProductCode(): JsonResponse
    {
        return response()->json([
            'code' => $this->inventoryService->nextProductCode(),
        ]);
    }

    public function quickStoreProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:products,code',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'purchase_price.required' => 'Indica el costo de compra.',
            'code.unique' => 'Ese código ya existe en el inventario.',
        ]);

        $categoryId = $validated['category_id'] ?? Category::query()->value('id');

        if (! $categoryId) {
            return response()->json([
                'message' => 'Crea al menos una categoría antes de registrar productos.',
                'errors' => ['category_id' => ['Crea al menos una categoría antes de registrar productos.']],
            ], 422);
        }

        $purchasePrice = (float) $validated['purchase_price'];
        $salePrice = isset($validated['sale_price'])
            ? (float) $validated['sale_price']
            : round($purchasePrice / 0.85, 2);
        $code = $validated['code'] ?? $this->inventoryService->nextProductCode();
        $supplierId = isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null;

        $product = DB::transaction(function () use ($categoryId, $validated, $purchasePrice, $salePrice, $code, $supplierId) {
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $validated['name'],
                'code' => $code,
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'stock' => 0,
                'unit' => 'unidad',
                'low_stock_threshold' => 5,
                'status' => 'active',
            ]);

            $this->pricing->syncProductToDefaultList($product);

            if ($supplierId) {
                $product->suppliers()->syncWithoutDetaching([
                    $supplierId => ['purchase_price' => $purchasePrice],
                ]);
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'price' => $purchasePrice,
                'has_supplier_price' => $supplierId !== null,
            ],
        ]);
    }

    public function show($id)
    {
        $purchase = Purchase::with('details.product', 'supplier', 'warehouse')->findOrFail($id);

        return view('compras.show', compact('purchase'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('compras.create', compact('suppliers', 'warehouses', 'categories'));
    }

    public function store(PurchaseRequest $request)
    {
        $data = $request->validated();
        $warehouseId = $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? null);

        try {
            DB::transaction(function () use ($data, $request, $warehouseId) {
                $purchase = Purchase::create([
                    'supplier_id' => $data['supplier_id'],
                    'user_id' => $request->user()?->id ?? ($data['user_id'] ?? 1),
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                    'status' => $data['status'] ?? 'completed',
                ]);

                $subtotal = 0;
                $taxTotal = 0;

                foreach ($data['items'] as $item) {
                    $lineNet = $item['quantity'] * $item['price'];
                    $product = Product::find($item['product_id']);
                    $rate = $product?->effectiveTaxRate() ?? Tax::defaultRate();
                    $lineTax = $lineNet * $rate;

                    PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $lineNet,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockIn(
                        $product,
                        (float) $item['quantity'],
                        'purchase:'.$purchase->id,
                        'Entrada por compra #'.$purchase->id,
                        $purchase->user_id,
                        $warehouseId,
                    );

                    $subtotal += $lineNet;
                    $taxTotal += $lineTax;
                }

                $purchase->update([
                    'subtotal' => round($subtotal, 2),
                    'tax_total' => round($taxTotal, 2),
                    'total' => round($subtotal + $taxTotal, 2),
                ]);

                $this->accountingService->recordPurchase($purchase->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra creada correctamente.');
    }

    public function edit($id)
    {
        $purchase = Purchase::with('details.product')->findOrFail($id);
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('compras.edit', compact('purchase', 'suppliers', 'warehouses', 'categories'));
    }

    public function productosPorProveedor($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);

        $products = $supplier->products()
            ->select(
                'products.id',
                'products.name',
                'products.code'
            )
            ->get()
            ->map(function ($product) {

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => $product->pivot->purchase_price ?? 0,
                ];
            });

        return response()->json($products);
    }

    public function update(PurchaseRequest $request, $id)
    {
        $data = $request->validated();
        $purchase = Purchase::with('details')->findOrFail($id);

        try {
            DB::transaction(function () use ($purchase, $data) {
                $warehouseId = $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? $purchase->warehouse_id);

                foreach ($purchase->details as $detail) {
                    $this->inventoryService->stockOut(
                        $detail->product,
                        (float) $detail->quantity,
                        'purchase_update_revert:'.$purchase->id,
                        'Reverso por edición de compra #'.$purchase->id,
                        $purchase->user_id,
                        false,
                        $purchase->warehouse_id,
                    );
                }

                // remove old details
                PurchaseDetail::where('purchase_id', $purchase->id)->delete();

                // create new details and update stock
                $subtotal = 0;
                $taxTotal = 0;
                foreach ($data['items'] as $item) {
                    $lineNet = $item['quantity'] * $item['price'];
                    $product = Product::find($item['product_id']);
                    $rate = $product?->effectiveTaxRate() ?? Tax::defaultRate();
                    $lineTax = $lineNet * $rate;

                    PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $lineNet,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockIn(
                        $product,
                        (float) $item['quantity'],
                        'purchase:'.$purchase->id,
                        'Entrada por compra #'.$purchase->id.' (editada)',
                        $purchase->user_id,
                        $warehouseId,
                    );

                    $subtotal += $lineNet;
                    $taxTotal += $lineTax;
                }

                $purchase->update([
                    'supplier_id' => $data['supplier_id'],
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'subtotal' => round($subtotal, 2),
                    'tax_total' => round($taxTotal, 2),
                    'total' => round($subtotal + $taxTotal, 2),
                    'status' => $data['status'] ?? $purchase->status,
                ]);

                $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra editada');
                $this->accountingService->recordPurchase($purchase->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('details')->findOrFail($id);

        try {
            DB::transaction(function () use ($purchase) {
                foreach ($purchase->details as $detail) {
                    $this->inventoryService->stockOut(
                        $detail->product,
                        (float) $detail->quantity,
                        'purchase_delete:'.$purchase->id,
                        'Reverso por eliminación de compra #'.$purchase->id,
                        $purchase->user_id,
                        false,
                        $purchase->warehouse_id,
                    );
                }

                PurchaseDetail::where('purchase_id', $purchase->id)->delete();
                $purchase->delete();

                $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra eliminada');
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
    }
}

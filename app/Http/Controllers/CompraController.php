<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use App\Services\PricingService;
use App\Services\PurchaseCostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompraController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private PosCatalogService $posCatalog,
        private PricingService $pricing,
        private PurchaseCostingService $purchaseCosting,
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

        return view('compras.index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'stats' => $stats,
            'companyCurrency' => $this->purchaseCosting->companyCurrency(),
            'companySymbol' => $this->purchaseCosting->companySymbol(),
            'equivalenceCurrency' => $this->purchaseCosting->equivalenceCurrency(),
            'equivalenceSymbol' => $this->purchaseCosting->currencySymbol($this->purchaseCosting->equivalenceCurrency()),
            'purchaseCosting' => $this->purchaseCosting,
        ]);
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
     * @return list<array<string, mixed>>
     */
    private function resolvePurchaseProductSearch(string $search, ?int $supplierId): array
    {
        if (strlen($search) < 2) {
            return [];
        }

        return Product::query()
            ->with(['baseUnit', 'unitConversions.unit'])
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

                $units = $this->purchaseCosting->purchaseUnitsFor($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => $supplierPrice ?? (float) ($product->purchase_price ?? 0),
                    'has_supplier_price' => $supplierPrice !== null,
                    'base_unit_id' => $product->base_unit_id,
                    'base_unit' => $product->baseUnit?->abbreviation ?? $product->unit,
                    'units' => $units,
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
            'base_unit_id' => 'nullable|exists:units,id',
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
        $baseUnit = isset($validated['base_unit_id'])
            ? Unit::query()->find($validated['base_unit_id'])
            : Unit::query()->where('abbreviation', 'und')->first();

        $product = DB::transaction(function () use ($categoryId, $validated, $purchasePrice, $salePrice, $code, $supplierId, $baseUnit) {
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $validated['name'],
                'code' => $code,
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'stock' => 0,
                'unit' => $baseUnit?->abbreviation ?? 'und',
                'base_unit_id' => $baseUnit?->id,
                'low_stock_threshold' => 5,
                'status' => 'active',
            ]);

            $this->pricing->syncProductToDefaultList($product);

            if ($supplierId) {
                $product->suppliers()->syncWithoutDetaching([
                    $supplierId => ['purchase_price' => $purchasePrice],
                ]);
            }

            return $product->load(['baseUnit', 'unitConversions.unit']);
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
                'base_unit_id' => $product->base_unit_id,
                'base_unit' => $product->baseUnit?->abbreviation ?? $product->unit,
                'units' => $this->purchaseCosting->purchaseUnitsFor($product),
            ],
        ]);
    }

    public function show($id)
    {
        $purchase = Purchase::with('details.product.baseUnit', 'details.unit', 'supplier', 'warehouse')->findOrFail($id);

        return view('compras.show', [
            'purchase' => $purchase,
            'purchaseTotals' => $this->purchaseCosting->presentTotals($purchase),
            'purchaseCosting' => $this->purchaseCosting,
        ]);
    }

    public function create()
    {
        return view('compras.create', $this->purchaseFormData());
    }

    public function store(PurchaseRequest $request)
    {
        $data = $request->validated();
        $warehouseId = $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? null);

        try {
            DB::transaction(function () use ($data, $request, $warehouseId) {
                $exchangeRate = $this->purchaseCosting->resolveExchangeRate(
                    $data['currency'],
                    isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
                );

                $purchase = Purchase::create([
                    'supplier_id' => $data['supplier_id'],
                    'user_id' => $request->user()?->id ?? ($data['user_id'] ?? 1),
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                    'status' => $data['status'] ?? 'completed',
                    'currency' => $data['currency'],
                    'exchange_rate' => $exchangeRate,
                    'foreign_subtotal' => 0,
                    'foreign_tax_total' => 0,
                    'foreign_total' => 0,
                ]);

                $this->syncPurchaseLines(
                    $purchase,
                    $data['items'],
                    $warehouseId,
                    $exchangeRate,
                    $purchase->status === 'completed',
                );
                $this->accountingService->recordPurchase($purchase->fresh());
            });
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra creada correctamente.');
    }

    public function edit($id)
    {
        $purchase = Purchase::with('details.product.baseUnit', 'details.product.unitConversions.unit', 'details.unit')->findOrFail($id);

        return view('compras.edit', array_merge($this->purchaseFormData(), compact('purchase')));
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
                $exchangeRate = $this->purchaseCosting->resolveExchangeRate(
                    $data['currency'],
                    isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
                );

                if ($purchase->status === 'completed') {
                    foreach ($purchase->details as $detail) {
                        $this->inventoryService->stockOut(
                            $detail->product,
                            $detail->inventoryQuantity(),
                            'purchase_update_revert:'.$purchase->id,
                            'Reverso por edición de compra #'.$purchase->id,
                            $purchase->user_id,
                            false,
                            $purchase->warehouse_id,
                        );
                    }
                }

                PurchaseDetail::where('purchase_id', $purchase->id)->delete();

                $purchase->update([
                    'supplier_id' => $data['supplier_id'],
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'status' => $data['status'] ?? $purchase->status,
                    'currency' => $data['currency'],
                    'exchange_rate' => $exchangeRate,
                ]);

                $this->syncPurchaseLines(
                    $purchase->fresh(),
                    $data['items'],
                    $warehouseId,
                    $exchangeRate,
                    $purchase->status === 'completed',
                );
                $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra editada');
                $this->accountingService->recordPurchase($purchase->fresh());
            });
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('details')->findOrFail($id);

        try {
            DB::transaction(function () use ($purchase) {
                if ($purchase->status === 'completed') {
                    foreach ($purchase->details as $detail) {
                        $this->inventoryService->stockOut(
                            $detail->product,
                            $detail->inventoryQuantity(),
                            'purchase_delete:'.$purchase->id,
                            'Reverso por eliminación de compra #'.$purchase->id,
                            $purchase->user_id,
                            false,
                            $purchase->warehouse_id,
                        );
                    }
                }

                PurchaseDetail::where('purchase_id', $purchase->id)->delete();
                $purchase->delete();

                $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra eliminada');
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function purchaseFormData(): array
    {
        return [
            'suppliers' => Supplier::orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
            'currencies' => $this->purchaseCosting->supportedCurrencies(),
            'companyCurrency' => $this->purchaseCosting->companyCurrency(),
            'companySymbol' => $this->purchaseCosting->companySymbol(),
            'exchangeRates' => $this->purchaseCosting->currentRatesToCompany(),
        ];
    }

    /**
     * @param  list<array{product_id: int, quantity: float|int|string, price: float|int|string, unit_id?: int|null}>  $items
     */
    private function syncPurchaseLines(
        Purchase $purchase,
        array $items,
        int $warehouseId,
        float $exchangeRate,
        bool $affectInventory,
    ): void {
        $foreignSubtotal = 0.0;
        $foreignTaxTotal = 0.0;
        $companySubtotal = 0.0;
        $companyTaxTotal = 0.0;

        foreach ($items as $item) {
            $product = Product::query()->with(['baseUnit', 'unitConversions'])->findOrFail($item['product_id']);
            $quantity = (float) $item['quantity'];
            $unitPriceForeign = (float) $item['price'];
            $resolved = $this->purchaseCosting->resolveLineQuantity(
                $product,
                $quantity,
                isset($item['unit_id']) ? (int) $item['unit_id'] : null,
            );

            $lineNetForeign = round($resolved['quantity'] * $unitPriceForeign, 2);
            $taxRate = $product->effectiveTaxRate() ?? Tax::defaultRate();
            $lineTaxForeign = round($lineNetForeign * $taxRate, 2);
            $lineNetCompany = $this->purchaseCosting->toCompanyAmount($lineNetForeign, $exchangeRate);
            $lineTaxCompany = $this->purchaseCosting->toCompanyAmount($lineTaxForeign, $exchangeRate);

            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'unit_id' => $resolved['unit_id'],
                'quantity' => $resolved['quantity'],
                'base_quantity' => $resolved['base_quantity'],
                'price' => $unitPriceForeign,
                'subtotal' => $lineNetForeign,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTaxForeign,
            ]);

            if ($affectInventory) {
                $this->inventoryService->stockIn(
                    $product,
                    $resolved['base_quantity'],
                    'purchase:'.$purchase->id,
                    'Entrada por compra #'.$purchase->id,
                    $purchase->user_id,
                    $warehouseId,
                );
            }

            if ($affectInventory && $resolved['base_quantity'] > 0) {
                $unitCostCompany = round($lineNetCompany / $resolved['base_quantity'], 4);
                $product->update(['purchase_price' => $unitCostCompany]);
            }

            $foreignSubtotal += $lineNetForeign;
            $foreignTaxTotal += $lineTaxForeign;
            $companySubtotal += $lineNetCompany;
            $companyTaxTotal += $lineTaxCompany;
        }

        $purchase->update([
            'subtotal' => round($companySubtotal, 2),
            'tax_total' => round($companyTaxTotal, 2),
            'total' => round($companySubtotal + $companyTaxTotal, 2),
            'foreign_subtotal' => round($foreignSubtotal, 2),
            'foreign_tax_total' => round($foreignTaxTotal, 2),
            'foreign_total' => round($foreignSubtotal + $foreignTaxTotal, 2),
            'exchange_rate' => $exchangeRate,
        ]);
    }
}

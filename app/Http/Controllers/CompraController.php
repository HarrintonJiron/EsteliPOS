<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\UpdatePurchaseStatusRequest;
use App\Models\Category;
use App\Models\InventoryMovement;
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
        $perPage = max(1, min(35, (int) $request->query('per_page', 15)));
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
                ->whereIn('status', ['pending', 'completed'])
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('total'),
            'completed_count' => Purchase::query()->where('status', 'completed')->count(),
            'pending_count' => Purchase::query()->where('status', 'pending')->count(),
            'ordered_count' => Purchase::query()->where('status', 'ordered')->count(),
            'invested_total' => (float) Purchase::query()
                ->whereIn('status', ['pending', 'completed'])
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
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ], [
            'supplier_id.required' => 'Selecciona un proveedor antes de buscar productos.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
        ]);

        return response()->json($this->resolvePurchaseProductSearch(
            trim($request->string('search')->toString()),
            (int) $validated['supplier_id'],
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
    private function resolvePurchaseProductSearch(string $search, int $supplierId): array
    {
        return Product::query()
            ->with([
                'baseUnit',
                'unitConversions.unit',
                'suppliers' => fn ($query) => $query->where('suppliers.id', $supplierId),
            ])
            ->where('status', 'active')
            ->where(function ($query) use ($supplierId) {
                $query->whereHas('suppliers', fn ($supplierQuery) => $supplierQuery->where('suppliers.id', $supplierId))
                    ->orWhereHas('purchaseDetails.purchase', function ($purchaseQuery) use ($supplierId) {
                        $purchaseQuery->where('supplier_id', $supplierId)
                            ->where('status', '!=', 'canceled');
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (Product $product) {
                $pivot = $product->suppliers->first()?->pivot;
                $supplierPrice = $pivot?->purchase_price !== null
                    ? (float) $pivot->purchase_price
                    : (float) ($product->purchase_price ?? 0);

                $units = $this->purchaseCosting->purchaseUnitsFor($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => $supplierPrice,
                    'has_supplier_price' => $pivot?->purchase_price !== null,
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
            'supplier_id' => 'required|exists:suppliers,id',
            'base_unit_id' => 'nullable|exists:units,id',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'purchase_price.required' => 'Indica el costo de compra.',
            'code.unique' => 'Ese código ya existe en el inventario.',
            'supplier_id.required' => 'Selecciona un proveedor antes de crear el producto.',
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
        $supplierId = (int) $validated['supplier_id'];
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

            $product->suppliers()->syncWithoutDetaching([
                $supplierId => ['purchase_price' => $purchasePrice],
            ]);

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
                'has_supplier_price' => true,
                'base_unit_id' => $product->base_unit_id,
                'base_unit' => $product->baseUnit?->abbreviation ?? $product->unit,
                'units' => $this->purchaseCosting->purchaseUnitsFor($product),
            ],
        ]);
    }

    public function show($id)
    {
        $purchase = Purchase::with('details.product.baseUnit', 'details.unit', 'supplier', 'warehouse', 'user')->findOrFail($id);

        return view('compras.show', [
            'purchase' => $purchase,
            'purchaseTotals' => $this->purchaseCosting->presentTotals($purchase),
            'purchaseCosting' => $this->purchaseCosting,
        ]);
    }

    public function proformaTicket(int $id)
    {
        $purchase = $this->purchaseProforma($id);

        return view('compras.ticket', [
            'purchase' => $purchase,
            'purchaseTotals' => $this->purchaseCosting->presentTotals($purchase),
        ]);
    }

    public function proformaPdf(int $id)
    {
        $purchase = $this->purchaseProforma($id);

        return view('compras.pdf', [
            'purchase' => $purchase,
            'purchaseTotals' => $this->purchaseCosting->presentTotals($purchase),
        ]);
    }

    public function destroyProformaDetail(int $id, int $detailId)
    {
        try {
            DB::transaction(function () use ($id, $detailId) {
                $purchase = Purchase::query()->lockForUpdate()->findOrFail($id);

                if ($purchase->status !== 'ordered') {
                    throw new RuntimeException('Solo se pueden quitar productos de una proforma en proceso.');
                }

                $details = PurchaseDetail::query()
                    ->where('purchase_id', $purchase->id)
                    ->lockForUpdate()
                    ->get();

                $detail = $details->firstWhere('id', $detailId);
                abort_unless($detail, 404);

                if ($details->count() <= 1) {
                    throw new RuntimeException('La proforma debe conservar al menos un producto. Puedes anularla si ya no la necesitas.');
                }

                $detail->delete();
                $this->recalculatePurchaseTotals($purchase);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Producto eliminado de la proforma.');
    }

    public function create()
    {
        return view('compras.create', array_merge($this->purchaseFormData(), [
            'purchaseMode' => 'immediate',
        ]));
    }

    public function createProforma()
    {
        return view('compras.create', array_merge($this->purchaseFormData(), [
            'purchaseMode' => 'proforma',
        ]));
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

                $settlement = $this->resolveSettlement($data);
                $purchase = Purchase::create([
                    'supplier_id' => $data['supplier_id'],
                    'user_id' => $request->user()?->id ?? ($data['user_id'] ?? 1),
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                    'status' => $settlement['status'],
                    'payment_type' => $settlement['payment_type'],
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
                    $purchase->affectsInventory(),
                );
                $this->assertSupplierCreditAvailable($purchase->fresh());
                $this->accountingService->recordPurchase($purchase->fresh());
            });
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $data['purchase_mode'] === 'proforma'
            ? 'Pedido proforma creado. El inventario se actualizará cuando confirmes la recepción.'
            : 'Compra creada correctamente.';

        return redirect()->route('compras.index')->with('success', $message);
    }

    public function edit($id)
    {
        $purchase = Purchase::with('details.product.baseUnit', 'details.product.unitConversions.unit', 'details.unit')->findOrFail($id);

        return view('compras.edit', array_merge($this->purchaseFormData(), compact('purchase')));
    }

    public function updateStatus(UpdatePurchaseStatusRequest $request, int $id)
    {
        $newStatus = (string) $request->validated('status');
        $paymentType = (string) ($request->validated('payment_type') ?? 'cash');

        try {
            DB::transaction(function () use ($id, $newStatus, $paymentType) {
                $purchase = Purchase::query()
                    ->with('details.product')
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->transitionPurchaseStatus($purchase, $newStatus, $paymentType);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = match ($newStatus) {
            'received' => $paymentType === 'credit'
                ? 'Mercadería recibida e ingresada al inventario. La compra quedó por pagar.'
                : 'Mercadería recibida e ingresada al inventario. La compra quedó pagada.',
            'completed' => 'Pago registrado. Se liquidó la deuda con el proveedor.',
            default => 'Compra anulada. Se revirtió el inventario cuando correspondía.',
        };

        return back()->with('success', $message);
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
        $purchase = Purchase::with('details.product')->findOrFail($id);

        try {
            DB::transaction(function () use ($purchase, $data) {
                $warehouseId = $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? $purchase->warehouse_id);
                $exchangeRate = $this->purchaseCosting->resolveExchangeRate(
                    $data['currency'],
                    isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
                );

                if ($purchase->affectsInventory()) {
                    $this->reversePurchaseInventory($purchase);
                }

                PurchaseDetail::where('purchase_id', $purchase->id)->delete();

                $settlement = $this->resolveSettlement($data, $purchase);
                $purchase->update([
                    'supplier_id' => $data['supplier_id'],
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'status' => $settlement['status'],
                    'payment_type' => $settlement['payment_type'],
                    'currency' => $data['currency'],
                    'exchange_rate' => $exchangeRate,
                ]);

                $this->syncPurchaseLines(
                    $purchase->fresh(),
                    $data['items'],
                    $warehouseId,
                    $exchangeRate,
                    $purchase->fresh()->affectsInventory(),
                );
                $this->assertSupplierCreditAvailable($purchase->fresh());
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
        $purchase = Purchase::with('details.product')->findOrFail($id);

        try {
            DB::transaction(function () use ($purchase) {
                if ($purchase->affectsInventory()) {
                    $this->reversePurchaseInventory($purchase);
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

    private function purchaseProforma(int $id): Purchase
    {
        $purchase = Purchase::query()
            ->with('details.product.baseUnit', 'details.unit', 'supplier', 'warehouse', 'user')
            ->findOrFail($id);

        abort_unless($purchase->status === 'ordered', 404);

        return $purchase;
    }

    private function recalculatePurchaseTotals(Purchase $purchase): void
    {
        $details = PurchaseDetail::query()->where('purchase_id', $purchase->id)->get();
        $foreignSubtotal = round((float) $details->sum('subtotal'), 2);
        $foreignTaxTotal = round((float) $details->sum('tax_amount'), 2);
        $exchangeRate = max((float) ($purchase->exchange_rate ?: 1), 0.000001);
        $companySubtotal = $this->purchaseCosting->toCompanyAmount($foreignSubtotal, $exchangeRate);
        $companyTaxTotal = $this->purchaseCosting->toCompanyAmount($foreignTaxTotal, $exchangeRate);

        $purchase->update([
            'subtotal' => $companySubtotal,
            'tax_total' => $companyTaxTotal,
            'total' => round($companySubtotal + $companyTaxTotal, 2),
            'foreign_subtotal' => $foreignSubtotal,
            'foreign_tax_total' => $foreignTaxTotal,
            'foreign_total' => round($foreignSubtotal + $foreignTaxTotal, 2),
        ]);
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

            if ($resolved['base_quantity'] > 0) {
                $unitCostCompany = round($lineNetCompany / $resolved['base_quantity'], 4);
                $product->suppliers()->syncWithoutDetaching([
                    $purchase->supplier_id => ['purchase_price' => $unitCostCompany],
                ]);
            }

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

    /**
     * @param  array<string, mixed>  $data
     * @return array{status: string, payment_type: string}
     */
    private function resolveSettlement(array $data, ?Purchase $purchase = null): array
    {
        $status = (string) ($data['status'] ?? $purchase?->status ?? 'completed');
        $paymentType = (string) ($data['payment_type'] ?? $purchase?->payment_type ?? '');

        if ($status === 'ordered') {
            return [
                'status' => 'ordered',
                'payment_type' => in_array($paymentType, ['cash', 'transfer', 'credit'], true) ? $paymentType : 'credit',
            ];
        }

        if ($status === 'canceled') {
            return [
                'status' => 'canceled',
                'payment_type' => in_array($paymentType, ['cash', 'transfer', 'credit'], true) ? $paymentType : 'cash',
            ];
        }

        if ($paymentType === 'credit' || $status === 'pending') {
            return ['status' => 'pending', 'payment_type' => 'credit'];
        }

        if ($paymentType === 'transfer') {
            return ['status' => 'completed', 'payment_type' => 'transfer'];
        }

        return ['status' => 'completed', 'payment_type' => 'cash'];
    }

    private function assertSupplierCreditAvailable(Purchase $purchase): void
    {
        if ($purchase->status !== 'pending') {
            return;
        }

        $supplier = Supplier::query()->lockForUpdate()->findOrFail($purchase->supplier_id);
        $limit = (float) ($supplier->credit_limit ?? 0);

        if ($limit <= 0) {
            return;
        }

        $used = (float) $supplier->purchases()
            ->where('status', 'pending')
            ->where('id', '!=', $purchase->id)
            ->sum('total');
        $available = round($limit - $used, 2);

        if ((float) $purchase->total > $available + 0.00001) {
            throw new RuntimeException(
                'Esta compra a crédito supera el límite del proveedor. Disponible: C$ '.number_format(max(0, $available), 2).'.'
            );
        }
    }

    private function transitionPurchaseStatus(Purchase $purchase, string $newStatus, string $paymentType = 'cash'): void
    {
        $current = $purchase->status;

        if ($current === $newStatus) {
            throw new RuntimeException(
                $newStatus === 'canceled'
                    ? 'Esta compra ya está anulada.'
                    : 'Esta compra ya está pagada.'
            );
        }

        if ($current === 'canceled') {
            throw new RuntimeException('Una compra anulada no se puede cambiar desde aquí.');
        }

        if ($newStatus === 'canceled') {
            $this->reversePurchaseInventory($purchase);
            $purchase->update(['status' => 'canceled']);
            $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra anulada');

            return;
        }

        if ($current === 'ordered') {
            if ($newStatus !== 'received') {
                throw new RuntimeException('Este pedido debe confirmarse como recibido antes de registrarlo como compra.');
            }

            if ($this->purchaseHasStockEntry($purchase)) {
                throw new RuntimeException('La mercadería de este pedido ya fue ingresada al inventario.');
            }

            $receivedStatus = $paymentType === 'credit' ? 'pending' : 'completed';
            $receivedPaymentType = match ($paymentType) {
                'credit' => 'credit',
                'transfer' => 'transfer',
                default => 'cash',
            };

            $this->applyPendingInventory($purchase);
            $purchase->update([
                'status' => $receivedStatus,
                'payment_type' => $receivedPaymentType,
            ]);
            $this->assertSupplierCreditAvailable($purchase->fresh());
            $this->accountingService->recordPurchase($purchase->fresh());

            return;
        }

        if ($newStatus !== 'completed' || $current !== 'pending') {
            throw new RuntimeException('Solo se pueden pagar compras pendientes a crédito.');
        }

        if (! $this->purchaseHasStockEntry($purchase)) {
            $this->applyPendingInventory($purchase);
        }

        $purchase->update([
            'status' => 'completed',
            'payment_type' => $paymentType === 'transfer' ? 'transfer' : 'cash',
        ]);
        $this->accountingService->voidForSource(Purchase::class, $purchase->id, 'Compra pagada');
        $this->accountingService->recordPurchase($purchase->fresh());
    }

    private function purchaseHasStockEntry(Purchase $purchase): bool
    {
        return InventoryMovement::query()
            ->where('reference', 'purchase:'.$purchase->id)
            ->where('type', 'in')
            ->exists();
    }

    private function reversePurchaseInventory(Purchase $purchase): void
    {
        if (! $this->purchaseHasStockEntry($purchase)) {
            return;
        }

        foreach ($purchase->details as $detail) {
            $qty = $detail->inventoryQuantity();
            if ($qty <= 0) {
                continue;
            }

            $this->inventoryService->stockOut(
                $detail->product,
                $qty,
                'purchase_cancel:'.$purchase->id,
                'Reverso por anulación de compra #'.$purchase->id,
                $purchase->user_id,
                false,
                $purchase->warehouse_id,
            );
        }
    }

    private function applyPendingInventory(Purchase $purchase): void
    {
        $warehouseId = $this->posCatalog->resolveWarehouseId($purchase->warehouse_id);
        $exchangeRate = max((float) ($purchase->exchange_rate ?: 1), 0.000001);

        foreach ($purchase->details as $detail) {
            $qty = $detail->inventoryQuantity();
            if ($qty <= 0) {
                continue;
            }

            $this->inventoryService->stockIn(
                $detail->product,
                $qty,
                'purchase:'.$purchase->id,
                'Entrada por compra #'.$purchase->id,
                $purchase->user_id,
                $warehouseId,
            );

            $lineNetCompany = $this->purchaseCosting->toCompanyAmount((float) $detail->subtotal, $exchangeRate);
            $detail->product->update(['purchase_price' => round($lineNetCompany / $qty, 4)]);
        }
    }
}

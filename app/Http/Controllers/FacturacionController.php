<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CajaSession;
use App\Models\Category;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\ExchangeRate;
use App\Models\NumberSequence;
use App\Models\PhoneTradeIn;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\BranchContextService;
use App\Services\CompanySettingsService;
use App\Services\CreditOverrideService;
use App\Services\CreditService;
use App\Services\ExchangeRateService;
use App\Services\ImageProcessingService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use App\Services\PricingService;
use App\Services\ProductGalleryService;
use App\Services\PurchaseCostingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class FacturacionController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private CreditService $creditService,
        private CreditOverrideService $creditOverrides,
        private ExchangeRateService $exchangeRates,
        private PosCatalogService $posCatalog,
        private PricingService $pricing,
        private BranchContextService $branches,
        private CompanySettingsService $companySettings,
    ) {}

    private function nextInvoiceNumber(): string
    {
        return NumberSequence::getNext('factura');
    }

    public function index(Request $request)
    {
        $perPage = max(1, min(35, (int) $request->query('per_page', 15)));
        $branchId = $request->user()?->branch_id;
        $query = Sale::with('client', 'user')->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        if ($request->filled('search')) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            })->orWhere('id', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sales = $query->latest()->paginate($perPage);
        $clients = Client::orderBy('name')->get();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $salesForBranch = fn () => Sale::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
        $stats = [
            'today_total' => (float) $salesForBranch()->whereDate('date', today())->where('status', 'completed')->sum('total'),
            'month_total' => (float) $salesForBranch()->whereBetween('date', [$monthStart, $monthEnd])->where('status', 'completed')->sum('total'),
            'month_count' => (int) $salesForBranch()->whereBetween('date', [$monthStart, $monthEnd])->where('status', 'completed')->count(),
            'pending' => (int) $salesForBranch()->where('status', 'pending')->count(),
        ];

        return view('facturacion.index', compact('sales', 'clients', 'stats'));
    }

    public function create()
    {
        return redirect()->route('facturacion.pos');
    }

    public function store(SaleRequest $request)
    {
        $data = $request->validated();

        // set user_id from authenticated user if available, otherwise fallback to provided or to 1
        $data['user_id'] = $request->user()?->id ?? ($data['user_id'] ?? 1);

        $sale = null;
        $amountReceived = $request->input('amount_received', 0);
        $cashSession = $data['payment_type'] === 'cash'
            ? CajaSession::currentForUser($data['user_id'])
            : null;
        if ($data['payment_type'] === 'cash' && ! $cashSession) {
            return back()->withInput()->with('error', 'Debes abrir una caja antes de registrar una venta en efectivo.');
        }

        try {
            DB::transaction(function () use ($data, &$sale, $cashSession) {
                $invoiceNumber = $data['invoice_number'] ?? null;
                if (! $invoiceNumber) {
                    $invoiceNumber = $this->nextInvoiceNumber();
                }

                $status = $data['payment_type'] === 'credit' ? 'pending' : 'completed';
                $client = Client::find($data['client_id']);
                $billingDocumentType = $data['billing_document_type']
                    ?? ($client?->isCompany() ? 'ruc' : ($client?->cedula ? 'cedula' : null));
                $billingDocumentNumber = $data['billing_ruc']
                    ?? ($billingDocumentType === 'cedula' ? $client?->cedula : $client?->ruc);
                $warehouseId = $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? null);
                $branch = $this->branches->resolve($warehouseId, $cashSession, auth()->user()?->branch_id);
                $resolvedPriceList = $this->pricing->resolvePriceList($client?->price_list_id, $branch?->id);

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $data['client_id'],
                    'user_id' => $data['user_id'],
                    'branch_id' => $branch?->id,
                    'caja_session_id' => $cashSession?->id,
                    'warehouse_id' => $warehouseId,
                    'price_list_id' => $resolvedPriceList?->id,
                    'price_list_name' => $resolvedPriceList?->name,
                    'billing_name' => $data['billing_name'],
                    'billing_business_name' => $data['billing_business_name'] ?? null,
                    'billing_document_type' => $billingDocumentType,
                    'billing_ruc' => $billingDocumentNumber,
                    'billing_phone' => $data['billing_phone'] ?? null,
                    'billing_email' => $data['billing_email'] ?? null,
                    'billing_address' => $data['billing_address'] ?? null,
                    'date' => $data['date'],
                    'due_date' => $data['due_date'] ?? null,
                    'payment_type' => $data['payment_type'],
                    'tax_included' => (bool) $data['tax_included'],
                    'tax_rate' => Tax::defaultRate(),
                    'status' => $status,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                ]);

                $linesTotal = 0;
                $subtotalExcl = 0;
                $taxTotal = 0;
                $taxIncluded = (bool) $sale->tax_included;
                $priceListId = $resolvedPriceList?->id;

                foreach ($data['items'] as $item) {
                    $product = Product::query()
                        ->with(['baseUnit', 'unitConversions'])
                        ->findOrFail($item['product_id']);
                    $line = $this->posCatalog->resolveSaleLine(
                        $product,
                        (float) $item['quantity'],
                        isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                        $priceListId,
                    );
                    $rate = $product->effectiveTaxRate();
                    $lineGross = $line['quantity'] * $line['price'];

                    if ($taxIncluded) {
                        $lineNet = $rate > 0 ? ($lineGross / (1 + $rate)) : $lineGross;
                        $lineTax = $lineGross - $lineNet;
                    } else {
                        $lineNet = $lineGross;
                        $lineTax = $lineGross * $rate;
                    }

                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'unit_id' => $line['unit_id'],
                        'price_list_item_id' => $line['price_list_item_id'],
                        'price_min_quantity' => $line['price_min_quantity'],
                        'quantity' => $line['quantity'],
                        'unit_factor' => $line['unit_factor'],
                        'base_quantity' => $line['base_quantity'],
                        'price' => $line['price'],
                        ...$this->referencePriceData($line['price']),
                        'subtotal' => $lineGross,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockOut(
                        $product,
                        $line['base_quantity'],
                        'sale:'.$sale->id,
                        'Salida por factura #'.($sale->invoice_number ?? $sale->id),
                        $sale->user_id,
                        false,
                        $sale->warehouse_id,
                    );

                    $linesTotal += $lineGross;
                    $subtotalExcl += $lineNet;
                    $taxTotal += $lineTax;
                }

                $sale->update([
                    'tax_rate' => $subtotalExcl > 0 ? round($taxTotal / $subtotalExcl, 4) : 0,
                    'subtotal' => round($subtotalExcl, 2),
                    'tax_total' => round($taxTotal, 2),
                    'total' => round($subtotalExcl + $taxTotal, 2),
                ]);

                $this->accountingService->recordSale($sale->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Si es pago en efectivo, redirigir a la vista de cambio
        if ($data['payment_type'] === 'cash' && $sale) {
            $changeAmount = max(0, $amountReceived - $sale->total);

            return redirect()->route('facturacion.change', ['saleId' => $sale->id])
                ->with('changeAmount', $changeAmount);
        }

        return redirect()->route('facturacion.pos')
            ->with('success', 'Factura creada correctamente')
            ->with('sale_id', $sale?->id);
    }

    public function print(Request $request)
    {
        $saleId = $request->query('sale_id');
        $sale = $saleId ? Sale::with('details.product.baseUnit', 'details.unit', 'client')->find($saleId) : null;

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        return view('facturacion.print', compact('sale'));
    }

    public function pdf(Request $request)
    {
        $saleId = $request->query('sale_id');
        $sale = $saleId ? Sale::with('details.product.baseUnit', 'details.unit', 'client')->find($saleId) : null;

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        return view('facturacion.pdf', compact('sale'));
    }

    public function show($id)
    {
        $sale = Sale::with('details.product.baseUnit', 'details.unit', 'client')->find($id);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        return view('facturacion.show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with('details.product.baseUnit', 'details.unit')->find($id);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        if ($sale->status === 'canceled') {
            return redirect()->route('facturacion.show', $sale)->with('error', 'Una factura anulada es de solo lectura.');
        }
        if ($sale->cajaSession && $sale->cajaSession->status !== 'open') {
            return redirect()->route('facturacion.show', $sale)->with('error', 'Esta factura pertenece a una caja cerrada y es de solo lectura.');
        }
        if ($sale->payment_type === 'credit'
            && $this->creditService->outstandingBalanceForSale($sale) < (float) $sale->total - 0.00001) {
            return redirect()->route('facturacion.show', $sale)->with('error', 'La factura tiene abonos aplicados y ya no puede editarse.');
        }
        $products = Product::with(['category', 'tax', 'baseUnit', 'unitConversions.unit'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->posCatalog->serializeProduct($product));
        $clients = Client::orderBy('name')->get();

        return view('facturacion.edit', compact('sale', 'products', 'clients'));
    }

    public function update(SaleRequest $request, $id)
    {
        $data = $request->validated();
        $sale = Sale::find($id);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        if ($sale->status === 'canceled') {
            return redirect()->route('facturacion.show', $sale)->with('error', 'Una factura anulada no se puede editar.');
        }

        try {
            DB::transaction(function () use ($data, $sale) {
                $sale = Sale::query()->with('details.product')->lockForUpdate()->findOrFail($sale->id);
                if ($sale->status === 'canceled') {
                    throw new \RuntimeException('Una factura anulada no se puede editar.');
                }
                if ($sale->cajaSession && $sale->cajaSession->status !== 'open') {
                    throw new \RuntimeException('Esta factura pertenece a una caja cerrada y no puede editarse.');
                }
                if ($sale->payment_type === 'credit'
                    && $this->creditService->outstandingBalanceForSale($sale) < (float) $sale->total - 0.00001) {
                    throw new \RuntimeException('La factura tiene abonos aplicados y no puede editarse.');
                }

                $status = $data['payment_type'] === 'credit' ? 'pending' : 'completed';
                $client = Client::find($data['client_id']);
                $billingDocumentType = $data['billing_document_type']
                    ?? ($client?->isCompany() ? 'ruc' : ($client?->cedula ? 'cedula' : null));
                $billingDocumentNumber = $data['billing_ruc']
                    ?? ($billingDocumentType === 'cedula' ? $client?->cedula : $client?->ruc);

                // Revert previous stock changes
                foreach ($sale->details as $detail) {
                    $this->inventoryService->stockIn(
                        $detail->product,
                        $this->posCatalog->saleDetailBaseQuantity($detail),
                        'sale_update_revert:'.$sale->id,
                        'Reverso por edición de factura #'.($sale->invoice_number ?? $sale->id),
                        $sale->user_id,
                        $sale->warehouse_id,
                    );
                }

                // Delete old details
                $sale->details()->delete();

                // Update sale
                $resolvedPriceList = $this->pricing->resolvePriceList($client?->price_list_id);
                $sale->update([
                    'invoice_number' => $data['invoice_number'] ?? $sale->invoice_number,
                    'client_id' => $data['client_id'],
                    'warehouse_id' => $this->posCatalog->resolveWarehouseId($data['warehouse_id'] ?? $sale->warehouse_id),
                    'price_list_id' => $resolvedPriceList?->id,
                    'price_list_name' => $resolvedPriceList?->name,
                    'billing_name' => $data['billing_name'],
                    'billing_business_name' => $data['billing_business_name'] ?? null,
                    'billing_document_type' => $billingDocumentType,
                    'billing_ruc' => $billingDocumentNumber,
                    'billing_phone' => $data['billing_phone'] ?? null,
                    'billing_email' => $data['billing_email'] ?? null,
                    'billing_address' => $data['billing_address'] ?? null,
                    'date' => $data['date'],
                    'due_date' => $data['due_date'] ?? null,
                    'payment_type' => $data['payment_type'],
                    'tax_included' => (bool) $data['tax_included'],
                    'tax_rate' => Tax::defaultRate(),
                    'status' => $status,
                    'notes' => $data['notes'] ?? null,
                ]);

                $linesTotal = 0;
                $subtotalExcl = 0;
                $taxTotal = 0;
                $taxIncluded = (bool) $sale->tax_included;
                $priceListId = $resolvedPriceList?->id;

                foreach ($data['items'] as $item) {
                    $product = Product::query()
                        ->with(['baseUnit', 'unitConversions'])
                        ->findOrFail($item['product_id']);
                    $line = $this->posCatalog->resolveSaleLine(
                        $product,
                        (float) $item['quantity'],
                        isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                        $priceListId,
                    );
                    $rate = $product->effectiveTaxRate();
                    $lineGross = $line['quantity'] * $line['price'];

                    if ($taxIncluded) {
                        $lineNet = round($rate > 0 ? ($lineGross / (1 + $rate)) : $lineGross, 2);
                        $lineTax = round($lineGross - $lineNet, 2);
                    } else {
                        $lineNet = round($lineGross, 2);
                        $lineTax = round($lineNet * $rate, 2);
                    }

                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'unit_id' => $line['unit_id'],
                        'price_list_item_id' => $line['price_list_item_id'],
                        'price_min_quantity' => $line['price_min_quantity'],
                        'quantity' => $line['quantity'],
                        'unit_factor' => $line['unit_factor'],
                        'base_quantity' => $line['base_quantity'],
                        'price' => $line['price'],
                        ...$this->referencePriceData($line['price']),
                        'subtotal' => $lineNet,
                        'tax_rate' => $rate,
                        'tax_amount' => $lineTax,
                    ]);

                    $this->inventoryService->stockOut(
                        $product,
                        $line['base_quantity'],
                        'sale:'.$sale->id,
                        'Salida por factura #'.($sale->invoice_number ?? $sale->id).' (editada)',
                        $sale->user_id,
                        false,
                        $sale->warehouse_id,
                    );

                    $linesTotal += $lineGross;
                    $subtotalExcl += $lineNet;
                    $taxTotal += $lineTax;
                }

                $sale->update([
                    'tax_rate' => $subtotalExcl > 0 ? round($taxTotal / $subtotalExcl, 4) : 0,
                    'subtotal' => round($subtotalExcl, 2),
                    'tax_total' => round($taxTotal, 2),
                    'total' => round($subtotalExcl + $taxTotal, 2),
                ]);

                $this->accountingService->voidForSource(Sale::class, $sale->id, 'Factura editada');
                $this->accountingService->recordSale($sale->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('facturacion.show', $sale->id);
    }

    public function destroy($id)
    {
        $sale = Sale::find($id);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        try {
            DB::transaction(function () use ($sale) {
                $sale = Sale::query()->with(['details.product', 'cajaSession', 'client'])->lockForUpdate()->findOrFail($sale->id);
                if ($sale->status === 'canceled') {
                    throw new \RuntimeException('La factura ya estaba anulada.');
                }
                if ($sale->cajaSession && $sale->cajaSession->status !== 'open') {
                    throw new \RuntimeException('Esta factura pertenece a una caja cerrada. Registra una corrección en la caja actual.');
                }
                if ($sale->payment_type === 'credit'
                    && $this->creditService->outstandingBalanceForSale($sale) < (float) $sale->total - 0.00001) {
                    throw new \RuntimeException('La factura tiene abonos aplicados. No puede anularse hasta corregir esos abonos.');
                }

                $oldValues = $sale->toArray();

                // Revert stock changes, but preserve the fiscal document and its detail.
                foreach ($sale->details as $detail) {
                    if (! $detail->product) {
                        continue;
                    }
                    $this->inventoryService->stockIn(
                        $detail->product,
                        $this->posCatalog->saleDetailBaseQuantity($detail),
                        'sale_delete:'.$sale->id,
                        'Reverso por eliminación de factura #'.($sale->invoice_number ?? $sale->id),
                        $sale->user_id,
                        $sale->warehouse_id,
                    );
                }

                $this->accountingService->voidForSource(Sale::class, $sale->id, 'Factura anulada');
                $sale->update(['status' => 'canceled']);
                if ($sale->repair_order_id && ($repairOrder = RepairOrder::query()->lockForUpdate()->find($sale->repair_order_id))) {
                    $repairOrder->creditPayments()
                        ->where('notes', 'Pago final de la factura '.$sale->invoice_number)
                        ->delete();
                    $repairOrder->forceFill([
                        'sale_id' => null,
                        'invoiced_at' => null,
                        'status' => 'ready',
                        'payment_received_at' => null,
                    ])->save();
                    $repairOrder->syncPaymentStatus();
                }
                AuditLog::log(
                    'sale.canceled',
                    "Factura #{$sale->id} anulada",
                    $sale,
                    $oldValues,
                    $sale->fresh()->toArray(),
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('facturacion.index')->with('success', 'Factura anulada. El documento se conservó y el inventario y asiento fueron revertidos.');
    }

    /**
     * POS - Mostrar la interfaz de punto de venta
     */
    public function pos()
    {
        $userBranchId = request()->user()?->branch_id;
        $cashSession = CajaSession::currentForUser(request()->user()?->id);
        $assignedBranch = $cashSession?->branch
            ?? ($userBranchId ? Branch::query()->where('is_active', true)->find($userBranchId) : null);
        $defaultWarehouseId = $this->posCatalog->resolveWarehouseId($assignedBranch?->warehouse_id);
        $products = Product::with(['category', 'tax', 'baseUnit', 'unitConversions.unit', 'warehouseStocks.warehouse'])
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->map(fn (Product $product) => $this->posCatalog->serializeProduct($product, $defaultWarehouseId));

        Client::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
        );
        $clients = Client::with('priceList:id,name,code')->orderBy('name')->get()
            ->each(function (Client $client): void {
                foreach ($this->creditService->clientCreditSummary($client) as $key => $value) {
                    $client->setAttribute($key, $value);
                }
            });
        $categories = Category::orderBy('name')->get();
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when($assignedBranch, fn ($query) => $query->whereKey($assignedBranch->warehouse_id))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);
        $defaultTaxRate = Tax::defaultRate();
        $posExchangeRate = ExchangeRate::query()
            ->where('from_currency', 'USD')
            ->where('to_currency', 'NIO')
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first(['id', 'rate', 'effective_date']);
        $companyProfile = $this->companySettings->get();
        $currencySymbol = (string) ($companyProfile['currency_symbol'] ?? 'C$');
        $companyCurrency = (string) ($companyProfile['currency'] ?? 'NIO');
        $posReferenceFx = app(PurchaseCostingService::class)->posReferenceFx();

        return view('facturacion.pos', compact(
            'products',
            'clients',
            'categories',
            'warehouses',
            'defaultWarehouseId',
            'defaultTaxRate',
            'posExchangeRate',
            'companyProfile',
            'currencySymbol',
            'companyCurrency',
            'posReferenceFx',
        ));
    }

    /**
     * Looks up a phone by IMEI before it's accepted as a trade-in, so the cashier
     * knows up front whether this exact phone already passed through the shop
     * (sold before and now coming back) instead of finding out via a unique-
     * constraint error after filling the whole trade-in form.
     */
    public function lookupTradeInImei(string $imei): JsonResponse
    {
        $imei = trim($imei);
        $product = Product::withTrashed()->where('imei', $imei)->first();

        if (! $product) {
            return response()->json(['exists' => false]);
        }

        $lastSaleDetail = SaleDetail::query()
            ->where('product_id', $product->id)
            ->whereHas('sale')
            ->with('sale:id,invoice_number,date')
            ->latest('id')
            ->first();

        return response()->json([
            'exists' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'model' => $product->model,
                'color' => $product->color,
                'battery_percentage' => $product->battery_percentage,
                'status' => $product->status,
                'stock' => (float) $product->stock,
                'trashed' => $product->trashed(),
                'lastSale' => $lastSaleDetail?->sale ? [
                    'invoice_number' => $lastSaleDetail->sale->invoice_number,
                    'date' => optional($lastSaleDetail->sale->date)->format('d/m/Y'),
                ] : null,
            ],
        ]);
    }

    public function posProducts(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $warehouseId = isset($validated['warehouse_id'])
            ? $this->posCatalog->resolveWarehouseId((int) $validated['warehouse_id'])
            : null;
        if ($warehouseId !== null) {
            try {
                $this->branches->resolve(
                    $warehouseId,
                    CajaSession::currentForUser($request->user()?->id),
                    $request->user()?->branch_id,
                );
            } catch (\RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
        }
        $priceListId = $this->posCatalog->resolvePriceListId($validated['client_id'] ?? null);

        $query = Product::query()
            ->with(['category:id,name', 'tax:id,rate,is_active', 'baseUnit', 'unitConversions.unit', 'warehouseStocks.warehouse'])
            ->where('status', 'active')
            ->when(isset($validated['category_id']), fn ($q) => $q->where('category_id', $validated['category_id']))
            ->when($warehouseId !== null, fn ($q) => $q->whereHas(
                'warehouseStocks',
                fn ($stockQuery) => $stockQuery
                    ->where('warehouse_id', $warehouseId)
                    ->where('quantity', '>', 0)
            ))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', $search)
                        ->orWhere('code', 'like', $search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhereHas('unitConversions', fn ($conversion) => $conversion
                            ->where('barcode', $search)
                            ->where('use_for_sale', true));
                });
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$search])
            ->orderBy('name')
            ->limit($search === '' ? 300 : 50)
            ->get();

        return response()->json(
            $query->map(function (Product $product) use ($warehouseId, $priceListId, $search) {
                $serialized = $this->posCatalog->serializeProduct($product, $warehouseId, $priceListId);
                $scanned = $search !== ''
                    ? $product->unitConversions->first(fn ($conversion) => $conversion->use_for_sale && $conversion->barcode === $search)
                    : null;

                if ($scanned) {
                    $serialized['default_unit_id'] = $scanned->unit_id;
                    $serialized['default_unit_label'] = $scanned->unit?->abbreviation ?? $product->baseUnitLabel();
                    $serialized['sale_units'] = collect($serialized['sale_units'])
                        ->map(function (array $unit) use ($scanned): array {
                            $unit['is_default'] = (int) $unit['id'] === (int) $scanned->unit_id;

                            return $unit;
                        })->all();
                }

                return $serialized;
            })
        );
    }

    public function updateProductImage(Request $request, int $product): JsonResponse
    {
        $productModel = Product::where('status', 'active')->findOrFail($product);

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
        ]);

        try {
            $newImagePath = app(ImageProcessingService::class)->storePublicImage(
                $validated['image'],
                'products',
                1600,
                1600,
            );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'image' => 'No se pudo procesar la imagen. Use JPG, PNG o WebP de hasta 8 MB y 20 megapíxeles.',
            ]);
        }

        try {
            app(ProductGalleryService::class)->replaceCover($productModel, $newImagePath);
        } catch (Throwable $exception) {
            app(ProductGalleryService::class)->discard([$newImagePath]);

            throw $exception;
        }

        $productModel->refresh();

        return response()->json([
            'success' => true,
            'product_id' => $productModel->id,
            'image_url' => $productModel->image_url,
            'message' => 'Imagen actualizada correctamente.',
        ]);
    }

    public function posDailyReport(Request $request)
    {
        $today = now()->toDateString();
        $currentSession = CajaSession::currentForUser($request->user()?->id);
        $sales = Sale::query()
            ->whereDate('date', $today)
            ->where('status', '!=', 'cancelled')
            ->when($currentSession?->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->get(['total', 'payment_type']);

        $labels = [
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia/Tarjeta',
            'credit' => 'Crédito',
        ];

        $byPayment = [];
        foreach ($labels as $type => $label) {
            $typeSales = $sales->where('payment_type', $type);
            $byPayment[$type] = [
                'label' => $label,
                'count' => $typeSales->count(),
                'total' => round((float) $typeSales->sum('total'), 2),
            ];
        }

        $invoiceCount = $sales->count();
        $totalSales = round((float) $sales->sum('total'), 2);

        return response()->json([
            'date' => now()->format('Y-m-d'),
            'cashier' => $request->user()?->name ?? 'N/A',
            'invoice_count' => $invoiceCount,
            'total_sales' => $totalSales,
            'average_ticket' => $invoiceCount > 0 ? round($totalSales / $invoiceCount, 2) : 0,
            'by_payment' => $byPayment,
        ]);
    }

    private function productsWithEffectiveTax(?int $limit = null)
    {
        return Product::with(['category', 'tax'])
            ->where('status', 'active')
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->each(function (Product $product) {
                $product->setAttribute('effective_tax_rate', $product->effectiveTaxRate());
            });
    }

    public function storePosExchangeRate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'rate' => ['required', 'numeric', 'min:0.000001', 'max:999999.999999'],
        ]);

        $attributes = [
            'from_currency' => 'USD',
            'to_currency' => 'NIO',
            'rate' => (float) $data['rate'],
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ];
        $existing = ExchangeRate::query()
            ->where('from_currency', 'USD')
            ->where('to_currency', 'NIO')
            ->whereDate('effective_date', $attributes['effective_date'])
            ->first();
        $exchangeRate = $existing
            ? $this->exchangeRates->updateRate($existing, $attributes)
            : $this->exchangeRates->createRate($attributes);

        return response()->json([
            'id' => $exchangeRate->id,
            'rate' => (float) $exchangeRate->rate,
            'effective_date' => $exchangeRate->effective_date->toDateString(),
        ], 201);
    }

    /**
     * POS - Procesar venta desde el interfaz de punto de venta
     */
    public function posStore(Request $request)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,card,transfer,credit',
            'client_id' => 'nullable|exists:clients,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'items' => 'required|json',
            'trade_ins' => 'nullable|json',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'amount_received' => 'nullable|numeric|min:0',
            'exchange_rate_id' => 'nullable|integer',
            'order_discount_pct' => 'nullable|numeric|min:0|max:100',
            'credit_override_token' => ['nullable', 'string', 'size:64'],
            'request_token' => ['nullable', 'uuid'],
        ]);

        $items = json_decode($validated['items'], true);
        $itemsValidator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:100000'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'items.*.product_id.exists' => 'Uno de los productos ya no existe.',
        ]);

        $itemsValidator->after(function ($validator) use ($items) {
            $presentations = [];
            foreach (is_array($items) ? $items : [] as $index => $item) {
                $key = ($item['product_id'] ?? '').':'.($item['unit_id'] ?? 'base');
                if (isset($presentations[$key])) {
                    $validator->errors()->add("items.{$index}.unit_id", 'La misma presentación ya aparece en el ticket; aumente la cantidad en esa línea.');
                }
                $presentations[$key] = true;
            }
        });

        if ($itemsValidator->fails()) {
            return back()->withErrors($itemsValidator)->withInput();
        }
        $items = $itemsValidator->validated()['items'];
        usort($items, fn (array $left, array $right) => ((int) $left['product_id']) <=> ((int) $right['product_id']));

        $tradeIns = json_decode($validated['trade_ins'] ?? '[]', true);
        $tradeInsValidator = Validator::make(['trade_ins' => $tradeIns], [
            'trade_ins' => ['nullable', 'array', 'max:10'],
            'trade_ins.*.imei' => ['required', 'string', 'max:30'],
            'trade_ins.*.name' => ['required', 'string', 'max:255'],
            'trade_ins.*.brand' => ['nullable', 'string', 'max:80'],
            'trade_ins.*.model' => ['nullable', 'string', 'max:120'],
            'trade_ins.*.color' => ['nullable', 'string', 'max:60'],
            'trade_ins.*.battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'trade_ins.*.category_id' => ['required', 'integer', 'exists:categories,id'],
            'trade_ins.*.trade_in_value' => ['required', 'numeric', 'min:0.01'],
            'trade_ins.*.sale_price' => ['required', 'numeric', 'min:0'],
        ]);
        if ($tradeInsValidator->fails()) {
            return back()->withErrors($tradeInsValidator)->withInput();
        }
        $tradeIns = $tradeInsValidator->validated()['trade_ins'] ?? [];
        $imeisSeen = [];
        foreach ($tradeIns as $tradeIn) {
            $imei = trim($tradeIn['imei']);
            if (isset($imeisSeen[$imei])) {
                return back()->withErrors(['trade_ins' => 'El mismo IMEI aparece más de una vez en el equipo recibido.'])->withInput();
            }
            $imeisSeen[$imei] = true;
        }

        $sale = null;
        $userId = $request->user()?->id ?? 1;
        $requestedPaymentType = $validated['payment_type'];
        $storedPaymentType = $requestedPaymentType === 'card' ? 'transfer' : $requestedPaymentType;
        $cashSession = $storedPaymentType === 'cash' ? CajaSession::currentForUser($userId) : null;
        if ($storedPaymentType === 'cash' && ! $cashSession) {
            return back()->withInput()->with('error', 'Debes abrir una caja antes de registrar una venta en efectivo.');
        }

        $existingSale = filled($validated['request_token'] ?? null)
            ? Sale::query()
                ->where('request_token', $validated['request_token'])
                ->where('user_id', $userId)
                ->first()
            : null;
        if ($existingSale) {
            return redirect()->route('facturacion.change', ['saleId' => $existingSale->id])
                ->with('changeAmount', (float) $existingSale->change_amount)
                ->with('success', 'La venta ya había sido procesada; no se duplicó.');
        }

        try {
            DB::transaction(function () use ($validated, $items, $tradeIns, &$sale, $userId, $cashSession, $request) {
                $cordobaRate = $this->resolvePosCordobaRate($validated['exchange_rate_id'] ?? null);
                $invoiceNumber = $this->nextInvoiceNumber();
                $requestedPaymentType = $validated['payment_type'];
                $storedPaymentType = $requestedPaymentType === 'card' ? 'transfer' : $requestedPaymentType;
                $status = $storedPaymentType === 'credit' ? 'pending' : 'completed';
                $notes = trim((string) ($validated['notes'] ?? ''));
                if ($requestedPaymentType === 'card') {
                    $notes .= ($notes !== '' ? ' | ' : '').'Pago con tarjeta';
                }
                if (filled($validated['reference_number'] ?? null)) {
                    $notes .= ($notes !== '' ? ' | ' : '').'Referencia: '.$validated['reference_number'];
                }

                $clientId = $validated['client_id'] ?? null;
                $client = $clientId
                    ? Client::query()
                        ->when($storedPaymentType === 'credit', fn ($query) => $query->lockForUpdate())
                        ->find($clientId)
                    : Client::where('code', 'GEN')->first();

                if (! $client) {
                    $client = Client::firstOrCreate(
                        ['code' => 'GEN'],
                        ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
                    );
                }

                $preferredWarehouseId = isset($validated['warehouse_id'])
                    ? (int) $validated['warehouse_id']
                    : $cashSession?->branch?->warehouse_id;
                $resolvedWarehouseId = $this->posCatalog->resolveWarehouseId($preferredWarehouseId);
                $branch = $this->branches->resolve($resolvedWarehouseId, $cashSession, $request->user()?->branch_id);
                $resolvedPriceList = $this->pricing->resolvePriceList($client->price_list_id, $branch?->id);
                $priceListId = $resolvedPriceList?->id;
                $saleWarehouseId = null;

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'request_token' => $validated['request_token'] ?? null,
                    'client_id' => $client->id,
                    'user_id' => $userId,
                    'branch_id' => $branch?->id,
                    'caja_session_id' => $cashSession?->id,
                    'warehouse_id' => $resolvedWarehouseId,
                    'price_list_id' => $resolvedPriceList?->id,
                    'price_list_name' => $resolvedPriceList?->name,
                    'billing_name' => $client->name,
                    'billing_business_name' => $client->business_name ?? null,
                    'billing_document_type' => $client->isCompany() ? 'ruc' : ($client->cedula ? 'cedula' : null),
                    'billing_ruc' => $client->document_number,
                    'billing_phone' => $client->phone ?? null,
                    'billing_email' => $client->email ?? null,
                    'billing_address' => $client->address ?? null,
                    'date' => now(),
                    'due_date' => $storedPaymentType === 'credit' ? $this->creditService->dueDateForClient($client) : null,
                    'payment_type' => $storedPaymentType,
                    'amount_paid' => $storedPaymentType === 'cash' ? ($validated['amount_received'] ?? 0) : 0,
                    'change_amount' => 0,
                    'tax_included' => false,
                    'tax_rate' => Tax::defaultRate(),
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null,
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                ]);

                $subtotalExcl = 0;
                $taxTotal = 0;
                $grossSubtotal = 0;
                $totalDiscountAmount = 0;
                $orderDiscountPct = min(100, max(0, (float) ($validated['order_discount_pct'] ?? 0)));

                foreach ($items as $item) {
                    $product = Product::query()->with(['baseUnit', 'unitConversions'])->findOrFail($item['product_id']);
                    $line = $this->posCatalog->resolveSaleLine(
                        $product,
                        (float) $item['quantity'],
                        isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                        $priceListId,
                        $branch?->id,
                    );

                    $discountPct = min(100, max(0, (float) ($item['discount'] ?? 0)));
                    $lineGross = $line['price'] * $line['quantity'];
                    $afterItemDiscount = $lineGross * (1 - $discountPct / 100);
                    $itemDiscountAmount = $lineGross - $afterItemDiscount;
                    $orderDiscountOnLine = $afterItemDiscount * ($orderDiscountPct / 100);
                    $lineDiscountAmount = round($itemDiscountAmount + $orderDiscountOnLine, 2);
                    $lineNet = round($afterItemDiscount - $orderDiscountOnLine, 2);

                    $rate = $product->effectiveTaxRate();
                    $lineTax = round($lineNet * $rate, 2);

                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'unit_id' => $line['unit_id'],
                        'price_list_item_id' => $line['price_list_item_id'],
                        'price_min_quantity' => $line['price_min_quantity'],
                        'quantity' => $line['quantity'],
                        'unit_factor' => $line['unit_factor'],
                        'base_quantity' => $line['base_quantity'],
                        'price' => $line['price'],
                        ...$this->referencePriceData($line['price'], $cordobaRate),
                        'discount_percentage' => $discountPct,
                        'discount_amount' => $lineDiscountAmount,
                        'subtotal' => $lineNet,
                        'tax_rate' => $rate,
                        'tax_amount' => $lineTax,
                    ]);

                    $lineWarehouseId = $this->posCatalog->resolveWarehouseForQuantity(
                        $product,
                        $line['base_quantity'],
                        $preferredWarehouseId,
                    );
                    $saleWarehouseId ??= $lineWarehouseId;

                    $this->inventoryService->stockOut(
                        $product,
                        $line['base_quantity'],
                        'pos_sale:'.$sale->id,
                        'Venta POS #'.$invoiceNumber,
                        $userId,
                        false,
                        $lineWarehouseId,
                    );

                    $grossSubtotal += $lineGross;
                    $totalDiscountAmount += $lineDiscountAmount;
                    $subtotalExcl += $lineNet;
                    $taxTotal += $lineTax;
                }

                $totalTradeInValue = 0.0;
                foreach ($tradeIns as $tradeIn) {
                    $totalTradeInValue += $this->processTradeIn(
                        $tradeIn,
                        $sale,
                        $userId,
                        $saleWarehouseId ?? $this->posCatalog->resolveWarehouseId($preferredWarehouseId),
                    );
                }
                $totalTradeInValue = round($totalTradeInValue, 2);

                $saleTotal = round($subtotalExcl + $taxTotal, 2);
                $amountDue = max(0, round($saleTotal - $totalTradeInValue, 2));

                $sale->update([
                    'warehouse_id' => $saleWarehouseId ?? $this->posCatalog->resolveWarehouseId($preferredWarehouseId),
                    'tax_rate' => $subtotalExcl > 0 ? round($taxTotal / $subtotalExcl, 4) : 0,
                    'subtotal' => round($subtotalExcl, 2),
                    'discount_amount' => round($totalDiscountAmount, 2),
                    'discount_percentage' => $orderDiscountPct,
                    'tax_total' => round($taxTotal, 2),
                    'trade_in_value' => $totalTradeInValue,
                    'total' => $saleTotal,
                    'change_amount' => $storedPaymentType === 'cash'
                        ? max(0, ($validated['amount_received'] ?? $amountDue) - $amountDue)
                        : 0,
                ]);

                if ($totalTradeInValue > $saleTotal) {
                    throw new \RuntimeException('El valor recibido en equipos no puede superar el total de la venta.');
                }

                if ($storedPaymentType === 'cash'
                    && (float) ($validated['amount_received'] ?? 0) < $amountDue) {
                    throw new \RuntimeException('El monto recibido es menor que el saldo a pagar después de descontar el equipo recibido.');
                }

                if ($storedPaymentType === 'credit') {
                    if (! $client->credit_enabled) {
                        throw new \RuntimeException('El cliente seleccionado no tiene crédito habilitado.');
                    }
                    // A trade-in phone is value received right now, exactly like a same-day
                    // payment, so it must reduce this sale's contribution to the client's
                    // outstanding balance before the credit-limit check runs below.
                    if ($totalTradeInValue > 0.00001) {
                        CreditPayment::create([
                            'client_id' => $client->id,
                            'sale_id' => $sale->id,
                            'amount' => $totalTradeInValue,
                            'payment_date' => now(),
                            'payment_type' => 'other',
                            'notes' => 'Recibido como parte de pago: equipo(s) entregado(s) en la misma venta.',
                            'user_id' => $userId,
                            'branch_id' => $sale->branch_id,
                            'caja_session_id' => null,
                        ]);
                    }
                    if ((float) $client->credit_limit > 0
                        && $this->creditService->pendingDebt($client) > (float) $client->credit_limit) {
                        $administratorId = $this->creditOverrides->consume(
                            (string) ($validated['credit_override_token'] ?? ''),
                            User::query()->findOrFail($userId),
                            $client,
                            (float) $sale->total,
                        );

                        if (! $administratorId) {
                            throw new \RuntimeException('La venta excede el límite de crédito y requiere autorización vigente de un administrador.');
                        }

                        AuditLog::query()->create([
                            'user_id' => $administratorId,
                            'action' => 'credit.override.used',
                            'model_type' => Sale::class,
                            'model_id' => $sale->id,
                            'description' => "Exceso de crédito aplicado a la venta {$sale->invoice_number}",
                            'new_values' => [
                                'cashier_id' => $userId,
                                'client_id' => $client->id,
                                'sale_total' => (float) $sale->total,
                                'credit_limit' => (float) $client->credit_limit,
                            ],
                        ]);
                    }
                }

                $this->accountingService->recordSale($sale->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException $e) {
            $existingSale = filled($validated['request_token'] ?? null)
                ? Sale::query()
                    ->where('request_token', $validated['request_token'])
                    ->where('user_id', $userId)
                    ->first()
                : null;

            if (! $existingSale) {
                throw $e;
            }

            return redirect()->route('facturacion.change', ['saleId' => $existingSale->id])
                ->with('changeAmount', (float) $existingSale->change_amount)
                ->with('success', 'La venta ya había sido procesada; no se duplicó.');
        }

        if ($sale) {
            $amountDue = max(0, (float) $sale->total - (float) $sale->trade_in_value);
            $amountReceived = $validated['amount_received'] ?? $amountDue;
            $changeAmount = $amountReceived - $amountDue;

            return redirect()->route('facturacion.change', ['saleId' => $sale->id])
                ->with('changeAmount', max(0, $changeAmount));
        }

        return back()->withErrors(['error' => 'Error al procesar la venta']);
    }

    /** @return array{reference_price: ?float, reference_currency: ?string} */
    private function referencePriceData(float $companyPrice, ?float $cordobaRate = null): array
    {
        if ($cordobaRate !== null) {
            return [
                'reference_price' => round($companyPrice * $cordobaRate, 4),
                'reference_currency' => 'NIO',
            ];
        }

        return ['reference_price' => round($companyPrice, 4), 'reference_currency' => 'USD'];
    }

    private function resolvePosCordobaRate(?int $exchangeRateId): ?float
    {
        if (! $exchangeRateId) {
            return null;
        }

        $rate = ExchangeRate::query()
            ->whereKey($exchangeRateId)
            ->where('from_currency', 'USD')
            ->where('to_currency', 'NIO')
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->value('rate');

        if ($rate === null || (float) $rate <= 0) {
            throw new \RuntimeException('La tasa seleccionada ya no está disponible. Selecciona una tasa vigente para mostrar los precios en córdobas.');
        }

        return (float) $rate;
    }

    /**
     * Registra un equipo recibido como parte de pago en una venta.
     * Si el IMEI ya existe (incluyendo productos eliminados), reactiva y
     * actualiza ese mismo producto en lugar de crear uno duplicado, de forma
     * que su historial de ventas previo siga vinculado al mismo registro.
     */
    private function processTradeIn(array $tradeIn, Sale $sale, ?int $userId, ?int $warehouseId): float
    {
        $imei = $tradeIn['imei'];
        $tradeInValue = round((float) $tradeIn['trade_in_value'], 2);

        $product = Product::withTrashed()->where('imei', $imei)->first();
        $wasReturningPhone = (bool) $product;

        $attributes = [
            'category_id' => $tradeIn['category_id'],
            'name' => $tradeIn['name'],
            'condition' => 'used',
            'brand' => $tradeIn['brand'] ?? null,
            'model' => $tradeIn['model'] ?? null,
            'color' => $tradeIn['color'] ?? null,
            'battery_percentage' => $tradeIn['battery_percentage'] ?? null,
            'imei' => $imei,
            'purchase_price' => $tradeInValue,
            'sale_price' => (float) $tradeIn['sale_price'],
            'status' => 'active',
        ];

        if ($product) {
            if (! $product->trashed() && (float) $product->stock > 0) {
                throw new \RuntimeException("El IMEI {$imei} ya está en el inventario ({$product->name}); no se puede recibir como parte de pago.");
            }

            if ($product->trashed()) {
                $product->restore();
            }

            $product->fill($attributes);
            $product->save();
        } else {
            $unit = Unit::where('abbreviation', 'und')->first();

            $data = array_merge($attributes, [
                'code' => $this->inventoryService->nextProductCode(),
                'stock' => 0,
                'unit' => $unit?->abbreviation ?? 'und',
                'base_unit_id' => $unit?->id,
            ]);

            try {
                $product = Product::create($data);
            } catch (UniqueConstraintViolationException $e) {
                $data['code'] = $this->inventoryService->nextProductCode();
                $product = Product::create($data);
            }
        }

        $this->inventoryService->stockIn(
            $product,
            1,
            'trade_in:'.$sale->id,
            'Recibido como parte de pago — Venta #'.$sale->invoice_number,
            $userId,
            $warehouseId
        );

        PhoneTradeIn::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'trade_in_value' => $tradeInValue,
            'was_returning_phone' => $wasReturningPhone,
            'notes' => $wasReturningPhone ? 'Teléfono reconocido por IMEI (venta anterior).' : null,
        ]);

        return $tradeInValue;
    }

    /**
     * Mostrar vista de cambio y confirmación de venta
     */
    public function change($saleId)
    {
        $sale = Sale::with('details.product.baseUnit', 'details.unit', 'user')->find($saleId);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        $changeAmount = session('changeAmount', 0);

        return view('facturacion.change', compact('sale', 'changeAmount'));
    }

    /**
     * Imprimir recibo térmico
     */
    public function receipt($saleId)
    {
        $sale = Sale::with('details.product.baseUnit', 'details.unit', 'user')->find($saleId);

        if (! $sale) {
            return $this->missingSaleResponse();
        }

        return view('facturacion.receipt', compact('sale'));
    }

    private function missingSaleResponse(): RedirectResponse
    {
        return redirect()->route('facturacion.index')
            ->with('error', 'La factura ya no está disponible; posiblemente fue eliminada en otra ventana.');
    }
}

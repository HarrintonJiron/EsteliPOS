<?php

namespace App\Http\Controllers;

use App\Models\CajaSession;
use App\Models\Category;
use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\BranchContextService;
use App\Services\CreditService;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProformaController extends Controller
{
    public function __construct(
        private AccountingService $accounting,
        private CreditService $credit,
        private InventoryService $inventoryService,
        private PosCatalogService $posCatalog,
        private PricingService $pricing,
        private BranchContextService $branches,
    ) {}

    private function defaultTaxRate(): float
    {
        return Tax::defaultRate();
    }

    private function nextProformaNumber(): string
    {
        $minimum = Proforma::query()
            ->whereNotNull('proforma_number')
            ->pluck('proforma_number')
            ->reduce(function (int $max, mixed $value): int {
                return is_string($value) && preg_match('/^PRO-([0-9]+)$/', $value, $matches) === 1
                    ? max($max, (int) $matches[1])
                    : $max;
            }, 0) + 1;

        return NumberSequence::getNextAtLeast('proforma', $minimum);
    }

    /**
     * Build authoritative stock warnings without preventing a quotation from
     * being saved. Quantities are grouped in case a crafted request repeats a
     * product on more than one line.
     *
     * @return array<int, string>
     */
    private function stockWarnings(array $items): array
    {
        $requested = collect($items)
            ->filter(fn (array $item): bool => isset($item['product_id']))
            ->map(function (array $item): array {
                $product = Product::query()->with(['baseUnit', 'unitConversions.unit'])->find($item['product_id']);
                if (! $product) {
                    return ['product_id' => (int) $item['product_id'], 'quantity' => 0];
                }
                $line = $this->posCatalog->resolveSaleLine(
                    $product,
                    max(0, (float) ($item['quantity'] ?? 0)),
                    isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                    null,
                );

                return ['product_id' => $product->id, 'quantity' => $line['base_quantity']];
            })
            ->groupBy('product_id')
            ->map(fn ($lines): float => $lines->sum('quantity'));

        if ($requested->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->whereIn('id', $requested->keys())
            ->get(['id', 'name', 'stock'])
            ->keyBy('id');

        return $requested->map(function (float $quantity, int $productId) use ($products): ?string {
            $product = $products->get($productId);
            if (! $product) {
                return null;
            }

            $stock = (float) $product->stock;
            if ($stock <= 0) {
                return "{$product->name}: no tiene existencias disponibles.";
            }

            if ($quantity > $stock) {
                return sprintf(
                    '%s: solicitaste %s y solo hay %s disponibles.',
                    $product->name,
                    number_format($quantity, 2),
                    number_format($stock, 2),
                );
            }

            return null;
        })->filter()->values()->all();
    }

    public function index(Request $request)
    {
        $query = Proforma::with('client', 'user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('proforma_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $proformas = $query->paginate(15)->withQueryString();
        $stats = [
            'month_total' => (float) Proforma::query()->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'month_count' => (int) Proforma::query()->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'accepted' => (int) Proforma::query()->where('status', 'accepted')->count(),
            'open' => (int) Proforma::query()->whereIn('status', ['draft', 'sent'])->count(),
        ];

        return view('proformas.index', compact('proformas', 'stats'));
    }

    public function pos()
    {
        $products = Product::with(['category', 'tax', 'baseUnit', 'unitConversions.unit', 'warehouseStocks.warehouse'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->posCatalog->serializeProduct($product));

        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $defaultTaxRate = $this->defaultTaxRate();

        return view('proformas.pos', compact('products', 'clients', 'categories', 'defaultTaxRate'));
    }

    public function products(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|integer|exists:clients,id',
        ]);
        $priceListId = $this->posCatalog->resolvePriceListId($validated['client_id'] ?? null);

        return response()->json(
            Product::with(['category', 'tax', 'baseUnit', 'unitConversions.unit', 'warehouseStocks.warehouse'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => $this->posCatalog->serializeProduct($product, null, $priceListId))
                ->values()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|json',
            'notes' => 'nullable|string|max:500',
            'expiry_days' => 'nullable|integer|min:1|max:365',
            'order_discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = json_decode($validated['items'], true);
        if (empty($items)) {
            return back()->withErrors(['items' => 'La proforma está vacía.']);
        }

        $stockWarnings = $this->stockWarnings($items);

        $proforma = null;
        $userId = $request->user()?->id ?? 1;
        $defaultTaxRate = $this->defaultTaxRate();

        DB::transaction(function () use ($validated, $items, &$proforma, $userId, $defaultTaxRate) {
            $clientId = $validated['client_id'] ?? null;
            $client = $clientId ? Client::find($clientId) : null;
            $resolvedPriceList = $this->pricing->resolvePriceList($client?->price_list_id);

            $expiryDays = (int) ($validated['expiry_days'] ?? 15);

            $proforma = Proforma::create([
                'proforma_number' => $this->nextProformaNumber(),
                'client_id' => $client?->id,
                'user_id' => $userId,
                'price_list_id' => $resolvedPriceList?->id,
                'price_list_name' => $resolvedPriceList?->name,
                'client_name' => $client?->name ?? 'Cliente General',
                'client_phone' => $client?->phone,
                'client_email' => $client?->email,
                'client_address' => $client?->address,
                'date' => now()->toDateString(),
                'expiry_date' => now()->addDays($expiryDays)->toDateString(),
                'tax_rate' => $defaultTaxRate,
                'tax_included' => false,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            $linesTotal = 0.0;
            $taxTotal = 0.0;
            $orderDiscountPct = (float) ($validated['order_discount_pct'] ?? 0);

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $discountPct = min(100, max(0, (float) ($item['discount'] ?? 0)));

                $product = Product::query()
                    ->with(['tax', 'baseUnit', 'unitConversions.unit'])
                    ->findOrFail($item['product_id'] ?? null);
                $line = $this->posCatalog->resolveSaleLine(
                    $product,
                    $quantity,
                    isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                    $resolvedPriceList?->id,
                );
                $price = $line['price'];
                $subtotal = $price * $quantity * (1 - $discountPct / 100) * (1 - $orderDiscountPct / 100);

                $rate = $product?->effectiveTaxRate() ?? $defaultTaxRate;
                $lineTax = $subtotal * $rate;

                ProformaDetail::create([
                    'proforma_id' => $proforma->id,
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? ($item['name'] ?? 'Producto'),
                    'unit_id' => $line['unit_id'],
                    'unit_factor' => $line['unit_factor'],
                    'base_quantity' => $line['base_quantity'],
                    'price_list_item_id' => $line['price_list_item_id'],
                    'price_min_quantity' => $line['price_min_quantity'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discountPct,
                    'subtotal' => $subtotal,
                ]);

                $linesTotal += $subtotal;
                $taxTotal += $lineTax;
            }

            $proforma->update([
                'tax_rate' => $linesTotal > 0 ? round($taxTotal / $linesTotal, 4) : $defaultTaxRate,
                'subtotal' => round($linesTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($linesTotal + $taxTotal, 2),
            ]);
        });

        $response = redirect()->route('proformas.show', $proforma->id)
            ->with('success', 'Proforma guardada correctamente.');

        if ($stockWarnings !== []) {
            $response->with('warning', 'Advertencia de stock: '.implode(' ', $stockWarnings));
        }

        return $response;
    }

    public function show($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('proformas.show', compact('proforma', 'warehouses'));
    }

    public function updateStatus(Request $request, $id)
    {
        $proforma = Proforma::find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        $status = $request->validate(['status' => 'required|in:draft,sent,accepted,rejected,expired'])['status'];
        $proforma->update(['status' => $status]);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $proforma = Proforma::find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        $proforma->details()->delete();
        $proforma->delete();

        return redirect()->route('proformas.index')->with('success', 'Proforma eliminada.');
    }

    public function pdf($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        return view('proformas.pdf', compact('proforma'));
    }

    public function ticket($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        return view('proformas.ticket', compact('proforma'));
    }

    /**
     * Convert an accepted proforma into a confirmed sale.
     */
    public function convertToSale(Request $request, $id)
    {
        $proforma = Proforma::with('details.product')->find($id);

        if (! $proforma) {
            return $this->missingProformaResponse();
        }

        $validated = $request->validate([
            'payment_type' => 'required|in:cash,card,transfer,credit',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);
        $requestedPaymentType = $validated['payment_type'];
        $paymentType = $requestedPaymentType === 'card' ? 'transfer' : $requestedPaymentType;
        $warehouseId = (int) $validated['warehouse_id'];
        $cashSession = $paymentType === 'cash'
            ? CajaSession::currentForUser($request->user()?->id)
            : null;
        if ($paymentType === 'cash' && ! $cashSession) {
            return back()->with('error', 'Debes abrir una caja antes de convertir una proforma en una venta en efectivo.');
        }

        $sale = null;
        $proformaMissing = false;

        try {
            DB::transaction(function () use ($proforma, $paymentType, $requestedPaymentType, $warehouseId, $cashSession, &$sale, $request, &$proformaMissing) {
                $proforma = Proforma::query()->with('details.product')->lockForUpdate()->find($proforma->id);

                if (! $proforma) {
                    $proformaMissing = true;

                    return;
                }

                if ($proforma->sale_id) {
                    throw new \RuntimeException('Esta proforma ya fue convertida en factura.');
                }
                $userId = $request->user()?->id ?? 1;
                $branch = $this->branches->resolve($warehouseId, $cashSession, $request->user()?->branch_id);

                $clientId = $proforma->client_id;
                if (! $clientId) {
                    $genericClient = Client::firstOrCreate(
                        ['code' => 'GEN'],
                        [
                            'name' => 'Cliente General',
                            'business_name' => 'Cliente General',
                            'ruc' => null,
                            'phone' => '',
                            'email' => '',
                            'address' => 'Cliente generado automáticamente para conversiones sin cliente.',
                            'credit_enabled' => false,
                            'credit_limit' => 0,
                            'credit_days' => 0,
                        ]
                    );
                    $clientId = $genericClient->id;
                }

                $invoiceNumber = NumberSequence::getNext('factura');

                $status = $paymentType === 'credit' ? 'pending' : 'completed';
                $client = Client::query()
                    ->when($paymentType === 'credit', fn ($query) => $query->lockForUpdate())
                    ->findOrFail($clientId);
                if ($paymentType === 'credit' && ! $this->credit->canGrantCredit($client, (float) $proforma->total)) {
                    throw new \RuntimeException('El cliente no tiene crédito disponible suficiente para esta proforma.');
                }

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $clientId,
                    'user_id' => $userId,
                    'branch_id' => $branch?->id,
                    'caja_session_id' => $cashSession?->id,
                    'warehouse_id' => $warehouseId,
                    'price_list_id' => $proforma->price_list_id,
                    'price_list_name' => $proforma->price_list_name,
                    'billing_name' => $proforma->client_name ?: 'Cliente General',
                    'billing_phone' => $proforma->client_phone,
                    'billing_email' => $proforma->client_email,
                    'billing_address' => $proforma->client_address,
                    'date' => now(),
                    'due_date' => $paymentType === 'credit' ? $this->credit->dueDateForClient($client) : null,
                    'payment_type' => $paymentType,
                    'tax_included' => $proforma->tax_included,
                    'tax_rate' => $proforma->tax_rate,
                    'status' => $status,
                    'notes' => 'Generada desde Proforma '.$proforma->proforma_number
                        .($requestedPaymentType === 'card' ? ' | Pago con tarjeta' : ''),
                    'subtotal' => $proforma->subtotal,
                    'tax_total' => $proforma->tax_total,
                    'total' => $proforma->total,
                ]);

                $remainingTax = round((float) $proforma->tax_total, 2);
                $detailCount = $proforma->details->count();
                foreach ($proforma->details as $index => $detail) {
                    $lineTax = $index === $detailCount - 1
                        ? $remainingTax
                        : round((float) $proforma->tax_total * ((float) $detail->subtotal / max(0.01, (float) $proforma->subtotal)), 2);
                    $remainingTax = round($remainingTax - $lineTax, 2);
                    $gross = round((float) $detail->quantity * (float) $detail->price, 2);
                    $discountAmount = round(max(0, $gross - (float) $detail->subtotal), 2);
                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $detail->product_id,
                        'unit_id' => $detail->unit_id,
                        'price_list_item_id' => $detail->price_list_item_id,
                        'price_min_quantity' => $detail->price_min_quantity,
                        'quantity' => $detail->quantity,
                        'unit_factor' => $detail->unit_factor,
                        'base_quantity' => $detail->base_quantity,
                        'price' => $detail->price,
                        'subtotal' => $detail->subtotal,
                        'discount_percentage' => $detail->discount,
                        'discount_amount' => $discountAmount,
                        'tax_rate' => (float) $detail->subtotal > 0 ? round($lineTax / (float) $detail->subtotal, 4) : 0,
                        'tax_amount' => $lineTax,
                    ]);

                    if ($detail->product_id) {
                        $product = Product::find($detail->product_id);
                        if ($product) {
                            $this->inventoryService->stockOut(
                                $product,
                                (float) ($detail->base_quantity ?? $detail->quantity),
                                'proforma_sale:'.$sale->id,
                                'Venta desde Proforma '.$proforma->proforma_number,
                                $userId,
                                false,
                                $warehouseId,
                            );
                        }
                    }
                }

                // Mark proforma as accepted
                $this->accounting->recordSale($sale->fresh('details'));
                $proforma->update(['status' => 'accepted', 'sale_id' => $sale->id]);
            });

            if ($proformaMissing) {
                return $this->missingProformaResponse();
            }

            return redirect()->route('facturacion.show', $sale->id)
                ->with('success', 'Proforma convertida a factura correctamente.');
        } catch (Throwable $exception) {
            return redirect()->back()
                ->with('error', 'No se pudo convertir la proforma. '.$exception->getMessage());
        }
    }

    private function missingProformaResponse(): RedirectResponse
    {
        return redirect()->route('proformas.index')
            ->with('error', 'La proforma ya no está disponible; posiblemente fue eliminada en otra ventana.');
    }
}

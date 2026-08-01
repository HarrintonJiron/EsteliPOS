<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Tax;
use App\Services\AccountingService;
use App\Services\CreditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FacturacionController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private CreditService $creditService,
    ) {}

    private function nextInvoiceNumber(): string
    {
        return NumberSequence::getNext('factura');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Sale::with('client', 'user');

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

        return view('facturacion.index', compact('sales', 'clients'));
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

        try {
            DB::transaction(function () use ($data, &$sale) {
                $invoiceNumber = $data['invoice_number'] ?? null;
                if (! $invoiceNumber) {
                    $invoiceNumber = $this->nextInvoiceNumber();
                }

                $status = $data['payment_type'] === 'credit' ? 'pending' : 'completed';

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $data['client_id'],
                    'user_id' => $data['user_id'],
                    'billing_name' => $data['billing_name'],
                    'billing_business_name' => $data['billing_business_name'] ?? null,
                    'billing_ruc' => $data['billing_ruc'] ?? null,
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

                foreach ($data['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $rate = $product?->effectiveTaxRate() ?? Tax::defaultRate();
                    $lineGross = $item['quantity'] * $item['price'];

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
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $lineGross,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockOut(
                        $product,
                        (int) $item['quantity'],
                        'sale:'.$sale->id,
                        'Salida por factura #'.($sale->invoice_number ?? $sale->id),
                        $sale->user_id,
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
        $sale = $saleId ? Sale::with('details.product', 'client')->find($saleId) : null;

        return view('facturacion.print', compact('sale'));
    }

    public function pdf(Request $request)
    {
        $saleId = $request->query('sale_id');
        $sale = $saleId ? Sale::with('details.product', 'client')->find($saleId) : null;

        return view('facturacion.pdf', compact('sale'));
    }

    public function show($id)
    {
        $sale = Sale::with('details.product', 'client')->findOrFail($id);

        return view('facturacion.show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with('details.product')->findOrFail($id);
        $products = $this->productsWithEffectiveTax();
        $clients = Client::orderBy('name')->get();

        return view('facturacion.edit', compact('sale', 'products', 'clients'));
    }

    public function update(SaleRequest $request, $id)
    {
        $data = $request->validated();
        $sale = Sale::findOrFail($id);

        try {
            DB::transaction(function () use ($data, $sale) {
                $status = $data['payment_type'] === 'credit' ? 'pending' : 'completed';

                // Revert previous stock changes
                foreach ($sale->details as $detail) {
                    $this->inventoryService->stockIn($detail->product, (int) $detail->quantity,
                        'sale_update_revert:'.$sale->id,
                        'Reverso por edición de factura #'.($sale->invoice_number ?? $sale->id), $sale->user_id);
                }

                // Delete old details
                $sale->details()->delete();

                // Update sale
                $sale->update([
                    'invoice_number' => $data['invoice_number'] ?? $sale->invoice_number,
                    'client_id' => $data['client_id'],
                    'billing_name' => $data['billing_name'],
                    'billing_business_name' => $data['billing_business_name'] ?? null,
                    'billing_ruc' => $data['billing_ruc'] ?? null,
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

                foreach ($data['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $rate = $product?->effectiveTaxRate() ?? Tax::defaultRate();
                    $lineGross = $item['quantity'] * $item['price'];

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
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $lineGross,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockOut($product, (int) $item['quantity'], 'sale:'.$sale->id,
                        'Salida por factura #'.($sale->invoice_number ?? $sale->id).' (editada)', $sale->user_id);

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
        $sale = Sale::findOrFail($id);

        try {
            DB::transaction(function () use ($sale) {
                // Revert stock changes
                foreach ($sale->details as $detail) {
                    $this->inventoryService->stockIn($detail->product, (int) $detail->quantity, 'sale_delete:'.$sale->id,
                        'Reverso por eliminación de factura #'.($sale->invoice_number ?? $sale->id), $sale->user_id);
                }

                $sale->details()->delete();
                $sale->delete();

                $this->accountingService->voidForSource(Sale::class, $sale->id, 'Factura eliminada');
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('facturacion.index');
    }

    /**
     * POS - Mostrar la interfaz de punto de venta
     */
    public function pos()
    {
        // Una carga inicial acotada evita que el POS crezca en memoria junto con todo el catálogo.
        $products = $this->productsWithEffectiveTax(300);
        Client::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
        );
        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $defaultTaxRate = Tax::defaultRate();

        return view('facturacion.pos', compact('products', 'clients', 'categories', 'defaultTaxRate'));
    }

    public function posProducts(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $query = Product::query()
            ->with(['category:id,name', 'tax:id,rate,is_active'])
            ->where('status', 'active')
            ->when(isset($validated['category_id']), fn ($q) => $q->where('category_id', $validated['category_id']))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', $search)
                        ->orWhere('code', 'like', $search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$search])
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($query->each(
            fn (Product $product) => $product->setAttribute('effective_tax_rate', $product->effectiveTaxRate())
        ));
    }

    private function productsWithEffectiveTax(?int $limit = null)
    {
        return Product::with(['category', 'tax'])
            ->where('status', 'active')
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->each(fn (Product $product) => $product->setAttribute('effective_tax_rate', $product->effectiveTaxRate()));
    }

    /**
     * POS - Procesar venta desde el interfaz de punto de venta
     */
    public function posStore(Request $request)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,card,transfer,credit',
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|json',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'amount_received' => 'nullable|numeric|min:0',
            'order_discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = json_decode($validated['items'], true);
        $itemsValidator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'items.*.product_id.distinct' => 'Un producto no puede aparecer repetido en el ticket.',
            'items.*.product_id.exists' => 'Uno de los productos ya no existe.',
        ]);

        if ($itemsValidator->fails()) {
            return back()->withErrors($itemsValidator)->withInput();
        }
        $items = $itemsValidator->validated()['items'];

        $sale = null;
        $userId = $request->user()?->id ?? 1;

        try {
            DB::transaction(function () use ($validated, $items, &$sale, $userId) {
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
                    ? Client::find($clientId)
                    : Client::where('code', 'GEN')->first();

                if (! $client) {
                    $client = Client::firstOrCreate(
                        ['code' => 'GEN'],
                        ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
                    );
                }

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $client->id,
                    'user_id' => $userId,
                    'billing_name' => $client->name,
                    'billing_business_name' => $client->business_name ?? null,
                    'billing_ruc' => $client->ruc ?? null,
                    'billing_phone' => $client->phone ?? null,
                    'billing_email' => $client->email ?? null,
                    'billing_address' => $client->address ?? null,
                    'date' => now(),
                    'due_date' => $storedPaymentType === 'credit' ? $this->creditService->dueDateForClient($client) : null,
                    'payment_type' => $storedPaymentType,
                    'tax_included' => false,
                    'tax_rate' => Tax::defaultRate(),
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null,
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'total' => 0,
                ]);

                $linesTotal = 0;
                $subtotalExcl = 0;
                $taxTotal = 0;

                foreach ($items as $item) {
                    $quantity = (int) $item['quantity'];
                    $product = Product::query()->findOrFail($item['product_id']);
                    // El navegador nunca es fuente de verdad para el precio base.
                    $price = (float) $product->sale_price;
                    $discountPct = min(100, max(0, (float) ($item['discount'] ?? 0)));
                    $orderDiscountPct = (float) ($validated['order_discount_pct'] ?? 0);
                    $lineNet = ($price * $quantity)
                        * (1 - $discountPct / 100)
                        * (1 - $orderDiscountPct / 100);

                    $rate = $product->effectiveTaxRate();
                    $lineTax = $lineNet * $rate;

                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $lineNet,
                        'tax_rate' => $rate,
                        'tax_amount' => round($lineTax, 2),
                    ]);

                    $this->inventoryService->stockOut($product, $quantity, 'pos_sale:'.$sale->id,
                        'Venta POS #'.$invoiceNumber, $userId);

                    $linesTotal += $lineNet;
                    $subtotalExcl += $lineNet;
                    $taxTotal += $lineTax;
                }

                $sale->update([
                    'tax_rate' => $subtotalExcl > 0 ? round($taxTotal / $subtotalExcl, 4) : 0,
                    'subtotal' => round($subtotalExcl, 2),
                    'tax_total' => round($taxTotal, 2),
                    'total' => round($subtotalExcl + $taxTotal, 2),
                ]);

                if ($storedPaymentType === 'cash'
                    && (float) ($validated['amount_received'] ?? 0) < (float) $sale->total) {
                    throw new \RuntimeException('El monto recibido es menor que el total de la venta.');
                }

                if ($storedPaymentType === 'credit') {
                    if (! $client->credit_enabled) {
                        throw new \RuntimeException('El cliente seleccionado no tiene crédito habilitado.');
                    }
                    if ((float) $client->credit_limit > 0
                        && $this->creditService->pendingDebt($client) > (float) $client->credit_limit) {
                        throw new \RuntimeException('La venta excede el límite de crédito disponible del cliente.');
                    }
                }

                $this->accountingService->recordSale($sale->fresh());
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        if ($sale) {
            $amountReceived = $validated['amount_received'] ?? $sale->total;
            $changeAmount = $amountReceived - $sale->total;

            return redirect()->route('facturacion.change', ['saleId' => $sale->id])
                ->with('changeAmount', max(0, $changeAmount));
        }

        return back()->withErrors(['error' => 'Error al procesar la venta']);
    }

    /**
     * Mostrar vista de cambio y confirmación de venta
     */
    public function change($saleId)
    {
        $sale = Sale::with('details.product', 'user')->findOrFail($saleId);
        $changeAmount = session('changeAmount', 0);

        return view('facturacion.change', compact('sale', 'changeAmount'));
    }

    /**
     * Imprimir recibo térmico
     */
    public function receipt($saleId)
    {
        $sale = Sale::with('details.product', 'user')->findOrFail($saleId);
        $changeAmount = request()->query('change', 0);

        return view('facturacion.receipt', compact('sale', 'changeAmount'));
    }
}

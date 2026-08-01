<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Tax;
use App\Services\AccountingService;
use App\Services\CreditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProformaController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryService $inventoryService,
        private CreditService $creditService,
    ) {}

    private function nextProformaNumber(): string
    {
        return NumberSequence::getNext('cotizacion');
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

        return view('proformas.index', compact('proformas'));
    }

    public function pos()
    {
        $products = Product::with(['category', 'tax'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->each(fn (Product $product) => $product->setAttribute('effective_tax_rate', $product->effectiveTaxRate()));

        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $defaultTaxRate = Tax::defaultRate();

        return view('proformas.pos', compact('products', 'clients', 'categories', 'defaultTaxRate'));
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
        $itemsValidator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        if ($itemsValidator->fails()) {
            return back()->withErrors($itemsValidator)->withInput();
        }
        $items = $itemsValidator->validated()['items'];

        $proforma = null;
        $userId = $request->user()?->id ?? 1;

        DB::transaction(function () use ($validated, $items, &$proforma, $userId) {
            $client = $validated['client_id']
                ? Client::find($validated['client_id'])
                : null;

            $expiryDays = (int) ($validated['expiry_days'] ?? 15);

            $proforma = Proforma::create([
                'proforma_number' => $this->nextProformaNumber(),
                'client_id' => $client?->id,
                'user_id' => $userId,
                'client_name' => $client?->name ?? 'Cliente General',
                'client_phone' => $client?->phone,
                'client_email' => $client?->email,
                'client_address' => $client?->address,
                'date' => now()->toDateString(),
                'expiry_date' => now()->addDays($expiryDays)->toDateString(),
                'tax_rate' => Tax::defaultRate(),
                'tax_included' => false,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            $linesTotal = 0;
            $taxTotal = 0;
            $orderDiscountPct = (float) ($validated['order_discount_pct'] ?? 0);

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];
                $product = Product::findOrFail($item['product_id']);
                $price = (float) $product->sale_price;
                $discountPct = min(100, max(0, (float) ($item['discount'] ?? 0)));
                $subtotal = $price * $quantity
                    * (1 - $discountPct / 100)
                    * (1 - $orderDiscountPct / 100);

                $rate = $product->effectiveTaxRate();

                ProformaDetail::create([
                    'proforma_id' => $proforma->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discountPct,
                    'subtotal' => $subtotal,
                ]);

                $linesTotal += $subtotal;
                $taxTotal += $subtotal * $rate;
            }

            $grandTotal = $linesTotal + $taxTotal;

            $proforma->update([
                'tax_rate' => $linesTotal > 0 ? round($taxTotal / $linesTotal, 4) : 0,
                'subtotal' => round($linesTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($grandTotal, 2),
            ]);
        });

        return redirect()->route('proformas.show', $proforma->id)
            ->with('success', 'Proforma guardada correctamente.');
    }

    public function show($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->findOrFail($id);

        return view('proformas.show', compact('proforma'));
    }

    public function updateStatus(Request $request, $id)
    {
        $proforma = Proforma::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:draft,sent,accepted,rejected,expired'])['status'];
        $proforma->update(['status' => $status]);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $proforma = Proforma::findOrFail($id);
        $proforma->details()->delete();
        $proforma->delete();

        return redirect()->route('proformas.index')->with('success', 'Proforma eliminada.');
    }

    public function pdf($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->findOrFail($id);

        return view('proformas.pdf', compact('proforma'));
    }

    public function ticket($id)
    {
        $proforma = Proforma::with('details.product', 'client', 'user')->findOrFail($id);

        return view('proformas.ticket', compact('proforma'));
    }

    /**
     * Convert an accepted proforma into a confirmed sale.
     */
    public function convertToSale(Request $request, $id)
    {
        $proforma = Proforma::with('details.product')->findOrFail($id);

        $paymentType = $request->validate([
            'payment_type' => 'required|in:cash,card,transfer,credit',
        ])['payment_type'];

        $sale = null;

        try {
            DB::transaction(function () use ($proforma, $paymentType, &$sale, $request) {
                $userId = $request->user()?->id ?? 1;

                $proforma = Proforma::query()->lockForUpdate()->with('details.product')->findOrFail($proforma->id);
                if ($proforma->status === 'accepted') {
                    throw new \RuntimeException('Esta proforma ya fue convertida en factura.');
                }

                $invoiceNumber = NumberSequence::getNext('factura');
                $storedPaymentType = $paymentType === 'card' ? 'transfer' : $paymentType;

                $status = $storedPaymentType === 'credit' ? 'pending' : 'completed';
                $client = $proforma->client ?: Client::firstOrCreate(
                    ['code' => 'GEN'],
                    ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
                );

                if ($storedPaymentType === 'credit' && ! $this->creditService->canGrantCredit($client, (float) $proforma->total)) {
                    throw new \RuntimeException('El cliente no tiene crédito disponible para esta factura.');
                }

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $client->id,
                    'user_id' => $userId,
                    'billing_name' => $proforma->client_name,
                    'billing_phone' => $proforma->client_phone,
                    'billing_email' => $proforma->client_email,
                    'billing_address' => $proforma->client_address,
                    'date' => now(),
                    'payment_type' => $storedPaymentType,
                    'due_date' => $storedPaymentType === 'credit' ? $this->creditService->dueDateForClient($client) : null,
                    'tax_included' => $proforma->tax_included,
                    'tax_rate' => $proforma->tax_rate,
                    'status' => $status,
                    'notes' => 'Generada desde Proforma '.$proforma->proforma_number,
                    'subtotal' => $proforma->subtotal,
                    'tax_total' => $proforma->tax_total,
                    'total' => $proforma->total,
                ]);

                foreach ($proforma->details as $detail) {
                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'subtotal' => $detail->subtotal,
                    ]);

                    if ($detail->product_id) {
                        $this->inventoryService->stockOut($detail->product, (int) $detail->quantity,
                            'proforma_sale:'.$sale->id, 'Venta desde Proforma '.$proforma->proforma_number, $userId);
                    }
                }

                $this->accountingService->recordSale($sale->fresh());

                // Mark proforma as accepted
                $proforma->update(['status' => 'accepted']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('facturacion.show', $sale->id)
            ->with('success', 'Proforma convertida a factura correctamente.');
    }
}

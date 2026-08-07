<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Tax;
use App\Services\InventoryService;
use App\Services\PosCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProformaController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private PosCatalogService $posCatalog,
    ) {}

    private const DEFAULT_TAX_RATE = 0.15;

    private function defaultTaxRate(): float
    {
        $rate = Tax::defaultRate();

        return $rate > 0 ? $rate : self::DEFAULT_TAX_RATE;
    }

    private function nextProformaNumber(): string
    {
        $maxNumber = Proforma::query()
            ->whereNotNull('proforma_number')
            ->pluck('proforma_number')
            ->filter(fn ($value) => is_string($value) && preg_match('/^PRO-[0-9]+$/', $value) === 1)
            ->map(function ($value) {
                return (int) substr($value, 4);
            })
            ->max();

        $next = (int) ($maxNumber ?? 0) + 1;

        return 'PRO-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
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
            ->each(function (Product $product) {
                $product->setAttribute('effective_tax_rate', $product->effectiveTaxRate());
                $product->image_url = $product->getRawOriginal('image_url');
            });

        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $defaultTaxRate = $this->defaultTaxRate();

        return view('proformas.pos', compact('products', 'clients', 'categories', 'defaultTaxRate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|json',
            'notes' => 'nullable|string|max:500',
            'expiry_days' => 'nullable|integer|min:1|max:365',
        ]);

        $items = json_decode($validated['items'], true);
        if (empty($items)) {
            return back()->withErrors(['items' => 'La proforma está vacía.']);
        }

        $proforma = null;
        $userId = $request->user()?->id ?? 1;
        $defaultTaxRate = $this->defaultTaxRate();

        DB::transaction(function () use ($validated, $items, &$proforma, $userId, $defaultTaxRate) {
            $clientId = $validated['client_id'] ?? null;
            $client = $clientId ? Client::find($clientId) : null;

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
                'tax_rate' => $defaultTaxRate,
                'tax_included' => false,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            $linesTotal = 0;

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $price = (float) ($item['price'] ?? 0);
                $discountPct = (float) ($item['discount'] ?? 0);
                $subtotal = $price * $quantity * (1 - $discountPct / 100);

                $product = Product::find($item['product_id'] ?? null);

                ProformaDetail::create([
                    'proforma_id' => $proforma->id,
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? ($item['name'] ?? 'Producto'),
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discountPct,
                    'subtotal' => $subtotal,
                ]);

                $linesTotal += $subtotal;
            }

            $rate = $defaultTaxRate;
            $taxTotal = $linesTotal * $rate;
            $grandTotal = $linesTotal + $taxTotal;

            $proforma->update([
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

                // Determine next invoice number
                $maxNum = Sale::query()
                    ->whereNotNull('invoice_number')
                    ->pluck('invoice_number')
                    ->filter(fn ($value) => is_string($value) && preg_match('/^\d+$/', $value) === 1)
                    ->map(fn ($value) => (int) $value)
                    ->max();
                $invoiceNumber = str_pad((string) ((int) ($maxNum ?? 0) + 1), 6, '0', STR_PAD_LEFT);

                $status = $paymentType === 'credit' ? 'pending' : 'completed';

                $warehouseId = $this->posCatalog->resolveWarehouseId(null);

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'client_id' => $clientId,
                    'user_id' => $userId,
                    'warehouse_id' => $warehouseId,
                    'billing_name' => $proforma->client_name ?: 'Cliente General',
                    'billing_phone' => $proforma->client_phone,
                    'billing_email' => $proforma->client_email,
                    'billing_address' => $proforma->client_address,
                    'date' => now(),
                    'payment_type' => $paymentType,
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
                        $product = Product::find($detail->product_id);
                        if ($product) {
                            $this->inventoryService->stockOut(
                                $product,
                                (float) $detail->quantity,
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
                $proforma->update(['status' => 'accepted']);
            });

            return redirect()->route('facturacion.show', $sale->id)
                ->with('success', 'Proforma convertida a factura correctamente.');
        } catch (Throwable $exception) {
            return redirect()->back()
                ->with('error', 'No se pudo convertir la proforma. '.$exception->getMessage());
        }
    }
}

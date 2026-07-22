<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaController extends Controller
{
    private const DEFAULT_TAX_RATE = 0.15;

    private function nextProformaNumber(): string
    {
        $max = (int) Proforma::query()
            ->whereNotNull('proforma_number')
            ->where('proforma_number', 'REGEXP', '^PRO-[0-9]+$')
            ->selectRaw("MAX(CAST(SUBSTRING(proforma_number, 5) AS UNSIGNED)) as max_num")
            ->value('max_num');

        return 'PRO-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = Proforma::with('client', 'user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('proforma_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
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
        $products = Product::with('category')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('proformas.pos', compact('products', 'clients', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'   => 'nullable|exists:clients,id',
            'items'       => 'required|json',
            'notes'       => 'nullable|string|max:500',
            'expiry_days' => 'nullable|integer|min:1|max:365',
        ]);

        $items = json_decode($validated['items'], true);
        if (empty($items)) {
            return back()->withErrors(['items' => 'La proforma está vacía.']);
        }

        $proforma = null;
        $userId = $request->user()?->id ?? 1;

        DB::transaction(function () use ($validated, $items, &$proforma, $userId) {
            $client = $validated['client_id']
                ? Client::find($validated['client_id'])
                : null;

            $expiryDays = (int) ($validated['expiry_days'] ?? 15);

            $proforma = Proforma::create([
                'proforma_number' => $this->nextProformaNumber(),
                'client_id'       => $client?->id,
                'user_id'         => $userId,
                'client_name'     => $client?->name ?? 'Cliente General',
                'client_phone'    => $client?->phone,
                'client_email'    => $client?->email,
                'client_address'  => $client?->address,
                'date'            => now()->toDateString(),
                'expiry_date'     => now()->addDays($expiryDays)->toDateString(),
                'tax_rate'        => self::DEFAULT_TAX_RATE,
                'tax_included'    => false,
                'status'          => 'draft',
                'notes'           => $validated['notes'] ?? null,
                'subtotal'        => 0,
                'tax_total'       => 0,
                'total'           => 0,
            ]);

            $linesTotal = 0;

            foreach ($items as $item) {
                $quantity    = (float) ($item['quantity'] ?? 1);
                $price       = (float) ($item['price'] ?? 0);
                $discountPct = (float) ($item['discount'] ?? 0);
                $subtotal    = $price * $quantity * (1 - $discountPct / 100);

                $product = Product::find($item['product_id'] ?? null);

                ProformaDetail::create([
                    'proforma_id'  => $proforma->id,
                    'product_id'   => $product?->id,
                    'product_name' => $product?->name ?? ($item['name'] ?? 'Producto'),
                    'quantity'     => $quantity,
                    'price'        => $price,
                    'discount'     => $discountPct,
                    'subtotal'     => $subtotal,
                ]);

                $linesTotal += $subtotal;
            }

            $rate        = self::DEFAULT_TAX_RATE;
            $taxTotal    = $linesTotal * $rate;
            $grandTotal  = $linesTotal + $taxTotal;

            $proforma->update([
                'subtotal'  => round($linesTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total'     => round($grandTotal, 2),
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

        DB::transaction(function () use ($proforma, $paymentType, &$sale, $request) {
            $userId = $request->user()?->id ?? 1;

            // Determine next invoice number
            $maxNum = (int) Sale::query()
                ->whereNotNull('invoice_number')
                ->where('invoice_number', 'REGEXP', '^[0-9]+$')
                ->max(DB::raw('CAST(invoice_number AS UNSIGNED)'));
            $invoiceNumber = str_pad((string) ($maxNum + 1), 6, '0', STR_PAD_LEFT);

            $status = $paymentType === 'credit' ? 'pending' : 'completed';

            $sale = Sale::create([
                'invoice_number'        => $invoiceNumber,
                'client_id'             => $proforma->client_id,
                'user_id'               => $userId,
                'billing_name'          => $proforma->client_name,
                'billing_phone'         => $proforma->client_phone,
                'billing_email'         => $proforma->client_email,
                'billing_address'       => $proforma->client_address,
                'date'                  => now(),
                'payment_type'          => $paymentType,
                'tax_included'          => $proforma->tax_included,
                'tax_rate'              => $proforma->tax_rate,
                'status'                => $status,
                'notes'                 => 'Generada desde Proforma ' . $proforma->proforma_number,
                'subtotal'              => $proforma->subtotal,
                'tax_total'             => $proforma->tax_total,
                'total'                 => $proforma->total,
            ]);

            foreach ($proforma->details as $detail) {
                SaleDetail::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $detail->product_id,
                    'quantity'   => $detail->quantity,
                    'price'      => $detail->price,
                    'subtotal'   => $detail->subtotal,
                ]);

                if ($detail->product_id) {
                    Product::where('id', $detail->product_id)->decrement('stock', $detail->quantity);
                    $product = Product::find($detail->product_id);
                    InventoryMovement::create([
                        'product_id'  => $detail->product_id,
                        'type'        => 'out',
                        'quantity'    => $detail->quantity,
                        'stock_after' => $product?->stock,
                        'reference'   => 'proforma_sale:' . $sale->id,
                        'note'        => 'Venta desde Proforma ' . $proforma->proforma_number,
                        'user_id'     => $userId,
                    ]);
                }
            }

            // Mark proforma as accepted
            $proforma->update(['status' => 'accepted']);
        });

        return redirect()->route('facturacion.show', $sale->id)
            ->with('success', 'Proforma convertida a factura correctamente.');
    }
}

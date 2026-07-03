<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Http\Requests\SaleRequest;


class FacturacionController extends Controller
{
    private const DEFAULT_TAX_RATE = 0.15;

    private function nextInvoiceNumber(): string
    {
        $max = (int) Sale::query()
            ->whereNotNull('invoice_number')
            ->where('invoice_number', 'REGEXP', '^[0-9]+$')
            ->max(DB::raw('CAST(invoice_number AS UNSIGNED)'));

        return str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Sale::with('client', 'user');

        if ($request->filled('search')) {
            $query->whereHas('client', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })->orWhere('id', 'like', '%' . $request->search . '%');
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
        $products = Product::orderBy('name')->get();
        Client::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
        );

        $clients = Client::orderBy('name')->get();
        $nextInvoiceNumber = $this->nextInvoiceNumber();

        return view('facturacion.create', compact('products', 'clients', 'nextInvoiceNumber'));
    }

    public function store(SaleRequest $request)
    {
        $data = $request->validated();

        // set user_id from authenticated user if available, otherwise fallback to provided or to 1
        $data['user_id'] = $request->user()?->id ?? ($data['user_id'] ?? 1);

        $sale = null;

        DB::transaction(function () use ($data, &$sale) {
            $invoiceNumber = $data['invoice_number'] ?? null;
            if (!$invoiceNumber) {
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
                'tax_rate' => self::DEFAULT_TAX_RATE,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            $linesTotal = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                $product = Product::find($item['product_id']);
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'stock_after' => $product?->stock,
                    'reference' => 'sale:' . $sale->id,
                    'note' => 'Salida por factura #' . ($sale->invoice_number ?? $sale->id),
                    'user_id' => $sale->user_id,
                ]);

                $linesTotal += $subtotal;
            }

            $rate = (float) $sale->tax_rate;
            if ($sale->tax_included) {
                $subtotalExcl = $rate > 0 ? ($linesTotal / (1 + $rate)) : $linesTotal;
                $taxTotal = $linesTotal - $subtotalExcl;
                $grandTotal = $linesTotal;
            } else {
                $subtotalExcl = $linesTotal;
                $taxTotal = $linesTotal * $rate;
                $grandTotal = $linesTotal + $taxTotal;
            }

            $sale->update([
                'subtotal' => round($subtotalExcl, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($grandTotal, 2),
            ]);
        });

        return redirect()->route('facturacion.create')
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
        $products = Product::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('facturacion.edit', compact('sale', 'products', 'clients'));
    }

    public function update(SaleRequest $request, $id)
    {
        $data = $request->validated();
        $sale = Sale::findOrFail($id);

        DB::transaction(function () use ($data, $sale) {
            $status = $data['payment_type'] === 'credit' ? 'pending' : 'completed';

            // Revert previous stock changes
            foreach ($sale->details as $detail) {
                Product::where('id', $detail->product_id)->increment('stock', $detail->quantity);
                $product = Product::find($detail->product_id);
                InventoryMovement::create([
                    'product_id' => $detail->product_id,
                    'type' => 'in',
                    'quantity' => $detail->quantity,
                    'stock_after' => $product?->stock,
                    'reference' => 'sale_update_revert:' . $sale->id,
                    'note' => 'Reverso por edición de factura #' . ($sale->invoice_number ?? $sale->id),
                    'user_id' => $sale->user_id,
                ]);
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
                'tax_rate' => self::DEFAULT_TAX_RATE,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            $linesTotal = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                $product = Product::find($item['product_id']);
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'stock_after' => $product?->stock,
                    'reference' => 'sale:' . $sale->id,
                    'note' => 'Salida por factura #' . ($sale->invoice_number ?? $sale->id) . ' (editada)',
                    'user_id' => $sale->user_id,
                ]);

                $linesTotal += $subtotal;
            }

            $rate = (float) $sale->tax_rate;
            if ($sale->tax_included) {
                $subtotalExcl = $rate > 0 ? ($linesTotal / (1 + $rate)) : $linesTotal;
                $taxTotal = $linesTotal - $subtotalExcl;
                $grandTotal = $linesTotal;
            } else {
                $subtotalExcl = $linesTotal;
                $taxTotal = $linesTotal * $rate;
                $grandTotal = $linesTotal + $taxTotal;
            }

            $sale->update([
                'subtotal' => round($subtotalExcl, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($grandTotal, 2),
            ]);
        });

        return redirect()->route('facturacion.show', $sale->id);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        DB::transaction(function () use ($sale) {
            // Revert stock changes
            foreach ($sale->details as $detail) {
                Product::where('id', $detail->product_id)->increment('stock', $detail->quantity);
                $product = Product::find($detail->product_id);
                InventoryMovement::create([
                    'product_id' => $detail->product_id,
                    'type' => 'in',
                    'quantity' => $detail->quantity,
                    'stock_after' => $product?->stock,
                    'reference' => 'sale_delete:' . $sale->id,
                    'note' => 'Reverso por eliminación de factura #' . ($sale->invoice_number ?? $sale->id),
                    'user_id' => $sale->user_id,
                ]);
            }

            $sale->details()->delete();
            $sale->delete();
        });

        return redirect()->route('facturacion.index');
    }
}
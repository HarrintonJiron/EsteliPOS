<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Purchase::with('supplier', 'user');

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

        $purchases = $query->latest()->paginate($perPage);
        $suppliers = Supplier::orderBy('name')->get();

        return view('compras.index', compact('purchases', 'suppliers'));
    }

    public function show($id)
    {
        $purchase = Purchase::with('details.product', 'supplier')->findOrFail($id);

        return view('compras.show', compact('purchase'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('compras.create', compact('products', 'suppliers'));
    }

    public function store(PurchaseRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $request->user()?->id ?? ($data['user_id'] ?? 1),
                'date' => $data['date'],
                'total' => 0,
                'status' => $data['status'] ?? 'completed',
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                Product::where('id', $item['product_id'])->increment('stock', $item['quantity']);
                $product = Product::find($item['product_id']);
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'stock_after' => $product?->stock,
                    'reference' => 'purchase:' . $purchase->id,
                    'note' => 'Entrada por compra #' . $purchase->id,
                    'user_id' => $purchase->user_id,
                ]);

                $total += $subtotal;
            }

            $purchase->update(['total' => $total]);
        });

        return redirect()->route('compras.index')->with('success', 'Compra creada correctamente.');
    }

    public function edit($id)
    {
        $purchase = Purchase::with('details.product')->findOrFail($id);
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('compras.edit', compact('purchase', 'products', 'suppliers'));
    }

    public function update(PurchaseRequest $request, $id)
    {
        $data = $request->validated();
        $purchase = Purchase::with('details')->findOrFail($id);

        DB::transaction(function () use ($purchase, $data) {
            // revert stock for existing details
            foreach ($purchase->details as $detail) {
                Product::where('id', $detail->product_id)->decrement('stock', $detail->quantity);
                $product = Product::find($detail->product_id);
                InventoryMovement::create([
                    'product_id' => $detail->product_id,
                    'type' => 'out',
                    'quantity' => $detail->quantity,
                    'stock_after' => $product?->stock,
                    'reference' => 'purchase_update_revert:' . $purchase->id,
                    'note' => 'Reverso por edición de compra #' . $purchase->id,
                    'user_id' => $purchase->user_id,
                ]);
            }

            // remove old details
            PurchaseDetail::where('purchase_id', $purchase->id)->delete();

            // create new details and update stock
            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                Product::where('id', $item['product_id'])->increment('stock', $item['quantity']);
                $product = Product::find($item['product_id']);
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'stock_after' => $product?->stock,
                    'reference' => 'purchase:' . $purchase->id,
                    'note' => 'Entrada por compra #' . $purchase->id . ' (editada)',
                    'user_id' => $purchase->user_id,
                ]);

                $total += $subtotal;
            }

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'date' => $data['date'],
                'total' => $total,
                'status' => $data['status'] ?? $purchase->status,
            ]);
        });

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('details')->findOrFail($id);

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->details as $detail) {
                Product::where('id', $detail->product_id)->decrement('stock', $detail->quantity);
                $product = Product::find($detail->product_id);
                InventoryMovement::create([
                    'product_id' => $detail->product_id,
                    'type' => 'out',
                    'quantity' => $detail->quantity,
                    'stock_after' => $product?->stock,
                    'reference' => 'purchase_delete:' . $purchase->id,
                    'note' => 'Reverso por eliminación de compra #' . $purchase->id,
                    'user_id' => $purchase->user_id,
                ]);
            }

            PurchaseDetail::where('purchase_id', $purchase->id)->delete();
            $purchase->delete();
        });

        return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
    }
}
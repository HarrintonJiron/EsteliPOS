<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReparacionController extends Controller
{
    private function nextOrderNumber(): string
    {
        $max = (int) RepairOrder::query()
            ->whereNotNull('order_number')
            ->where('order_number', 'REGEXP', '^REP-[0-9]+$')
            ->selectRaw("MAX(CAST(SUBSTRING(order_number, 5) AS UNSIGNED)) as max_num")
            ->value('max_num');

        return 'REP-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = RepairOrder::with('technician')->latest();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('order_number', 'like', "%{$q}%")
                   ->orWhere('client_name', 'like', "%{$q}%")
                   ->orWhere('client_phone', 'like', "%{$q}%")
                   ->orWhere('device_brand', 'like', "%{$q}%")
                   ->orWhere('device_model', 'like', "%{$q}%")
                   ->orWhere('device_imei', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date')) {
            $query->whereDate('received_date', $request->date);
        }

        $orders = $query->paginate(15)->withQueryString();

        $stats = [
            'total'         => RepairOrder::count(),
            'received'      => RepairOrder::where('status', 'received')->count(),
            'in_repair'     => RepairOrder::whereIn('status', ['diagnosing', 'waiting_parts', 'in_repair'])->count(),
            'ready'         => RepairOrder::where('status', 'ready')->count(),
            'delivered'     => RepairOrder::where('status', 'delivered')->count(),
        ];

        return view('reparaciones.index', compact('orders', 'stats'));
    }

    public function create()
    {
        $clients    = Client::orderBy('name')->get();
        $technicians = User::orderBy('name')->get();
        $products   = Product::where('status', 'active')->orderBy('name')->get();

        return view('reparaciones.create', compact('clients', 'technicians', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'           => 'nullable|exists:clients,id',
            'client_name'         => 'required|string|max:150',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',
            'device_brand'        => 'required|string|max:60',
            'device_model'        => 'required|string|max:100',
            'device_color'        => 'nullable|string|max:50',
            'device_imei'         => 'nullable|string|max:60',
            'device_password'     => 'nullable|string|max:100',
            'accessories'         => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis'           => 'nullable|string',
            'repair_notes'        => 'nullable|string',
            'status'              => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority'            => 'required|in:low,normal,high,urgent',
            'technician_id'       => 'nullable|exists:users,id',
            'received_date'       => 'required|date',
            'estimated_date'      => 'nullable|date',
            'labor_cost'          => 'nullable|numeric|min:0',
            'advance_payment'     => 'nullable|numeric|min:0',
            'payment_type'        => 'required|in:cash,card,transfer',
            'items'               => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.price'       => 'required_with:items|numeric|min:0',
            'items.*.product_id'  => 'nullable|exists:products,id',
        ]);

        $order = null;

        DB::transaction(function () use ($validated, $request, &$order) {
            $items     = $validated['items'] ?? [];
            $partsCost = array_sum(array_map(fn($i) => ($i['quantity'] ?? 0) * ($i['price'] ?? 0), $items));
            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $total     = $laborCost + $partsCost;

            $order = RepairOrder::create([
                'order_number'        => $this->nextOrderNumber(),
                'client_id'           => $validated['client_id'] ?? null,
                'client_name'         => $validated['client_name'],
                'client_phone'        => $validated['client_phone'] ?? null,
                'client_email'        => $validated['client_email'] ?? null,
                'device_brand'        => $validated['device_brand'],
                'device_model'        => $validated['device_model'],
                'device_color'        => $validated['device_color'] ?? null,
                'device_imei'         => $validated['device_imei'] ?? null,
                'device_password'     => $validated['device_password'] ?? null,
                'accessories'         => $validated['accessories'] ?? null,
                'problem_description' => $validated['problem_description'],
                'diagnosis'           => $validated['diagnosis'] ?? null,
                'repair_notes'        => $validated['repair_notes'] ?? null,
                'status'              => $validated['status'],
                'priority'            => $validated['priority'],
                'technician_id'       => $validated['technician_id'] ?? null,
                'user_id'             => $request->user()?->id ?? 1,
                'received_date'       => $validated['received_date'],
                'estimated_date'      => $validated['estimated_date'] ?? null,
                'labor_cost'          => $laborCost,
                'parts_cost'          => $partsCost,
                'total'               => $total,
                'advance_payment'     => (float) ($validated['advance_payment'] ?? 0),
                'payment_type'        => $validated['payment_type'],
                'payment_status'      => $this->calcPaymentStatus($total, (float) ($validated['advance_payment'] ?? 0)),
            ]);

            foreach ($items as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['price'];
                RepairOrderItem::create([
                    'repair_order_id' => $order->id,
                    'product_id'      => $item['product_id'] ?? null,
                    'description'     => $item['description'],
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'subtotal'        => $subtotal,
                ]);
            }
        });

        return redirect()->route('reparaciones.show', $order->id)
            ->with('success', 'Orden de reparación creada correctamente.');
    }

    public function show($id)
    {
        $order = RepairOrder::with('items.product', 'client', 'technician', 'user')->findOrFail($id);

        return view('reparaciones.show', compact('order'));
    }

    public function edit($id)
    {
        $order       = RepairOrder::with('items.product')->findOrFail($id);
        $clients     = Client::orderBy('name')->get();
        $technicians = User::orderBy('name')->get();
        $products    = Product::where('status', 'active')->orderBy('name')->get();

        return view('reparaciones.edit', compact('order', 'clients', 'technicians', 'products'));
    }

    public function update(Request $request, $id)
    {
        $order = RepairOrder::findOrFail($id);

        $validated = $request->validate([
            'client_id'           => 'nullable|exists:clients,id',
            'client_name'         => 'required|string|max:150',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',
            'device_brand'        => 'required|string|max:60',
            'device_model'        => 'required|string|max:100',
            'device_color'        => 'nullable|string|max:50',
            'device_imei'         => 'nullable|string|max:60',
            'device_password'     => 'nullable|string|max:100',
            'accessories'         => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis'           => 'nullable|string',
            'repair_notes'        => 'nullable|string',
            'status'              => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority'            => 'required|in:low,normal,high,urgent',
            'technician_id'       => 'nullable|exists:users,id',
            'received_date'       => 'required|date',
            'estimated_date'      => 'nullable|date',
            'delivered_date'      => 'nullable|date',
            'labor_cost'          => 'nullable|numeric|min:0',
            'advance_payment'     => 'nullable|numeric|min:0',
            'payment_type'        => 'required|in:cash,card,transfer',
            'items'               => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.price'       => 'required_with:items|numeric|min:0',
            'items.*.product_id'  => 'nullable|exists:products,id',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $items     = $validated['items'] ?? [];
            $partsCost = array_sum(array_map(fn($i) => ($i['quantity'] ?? 0) * ($i['price'] ?? 0), $items));
            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $total     = $laborCost + $partsCost;
            $advance   = (float) ($validated['advance_payment'] ?? 0);

            // Mark delivered_date automatically
            $deliveredDate = $validated['delivered_date'] ?? null;
            if ($validated['status'] === 'delivered' && ! $deliveredDate && ! $order->delivered_date) {
                $deliveredDate = now()->toDateString();
            }

            $order->update([
                'client_id'           => $validated['client_id'] ?? null,
                'client_name'         => $validated['client_name'],
                'client_phone'        => $validated['client_phone'] ?? null,
                'client_email'        => $validated['client_email'] ?? null,
                'device_brand'        => $validated['device_brand'],
                'device_model'        => $validated['device_model'],
                'device_color'        => $validated['device_color'] ?? null,
                'device_imei'         => $validated['device_imei'] ?? null,
                'device_password'     => $validated['device_password'] ?? null,
                'accessories'         => $validated['accessories'] ?? null,
                'problem_description' => $validated['problem_description'],
                'diagnosis'           => $validated['diagnosis'] ?? null,
                'repair_notes'        => $validated['repair_notes'] ?? null,
                'status'              => $validated['status'],
                'priority'            => $validated['priority'],
                'technician_id'       => $validated['technician_id'] ?? null,
                'received_date'       => $validated['received_date'],
                'estimated_date'      => $validated['estimated_date'] ?? null,
                'delivered_date'      => $deliveredDate,
                'labor_cost'          => $laborCost,
                'parts_cost'          => $partsCost,
                'total'               => $total,
                'advance_payment'     => $advance,
                'payment_type'        => $validated['payment_type'],
                'payment_status'      => $this->calcPaymentStatus($total, $advance),
            ]);

            $order->items()->delete();

            foreach ($items as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['price'];
                RepairOrderItem::create([
                    'repair_order_id' => $order->id,
                    'product_id'      => $item['product_id'] ?? null,
                    'description'     => $item['description'],
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'subtotal'        => $subtotal,
                ]);
            }
        });

        return redirect()->route('reparaciones.show', $order->id)
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy($id)
    {
        $order = RepairOrder::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return redirect()->route('reparaciones.index')
            ->with('success', 'Orden eliminada.');
    }

    public function updateStatus(Request $request, $id)
    {
        $order  = RepairOrder::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled'])['status'];

        $update = ['status' => $status];
        if ($status === 'delivered' && ! $order->delivered_date) {
            $update['delivered_date'] = now()->toDateString();
        }

        $order->update($update);

        return back()->with('success', 'Estado actualizado a: ' . $order->fresh()->statusLabel());
    }

    public function ticket($id)
    {
        $order = RepairOrder::with('items.product', 'technician')->findOrFail($id);

        return view('reparaciones.ticket', compact('order'));
    }

    public function pdf($id)
    {
        $order = RepairOrder::with('items.product', 'client', 'technician', 'user')->findOrFail($id);

        return view('reparaciones.pdf', compact('order'));
    }

    private function calcPaymentStatus(float $total, float $advance): string
    {
        if ($total <= 0 || $advance >= $total) return 'paid';
        if ($advance > 0)                      return 'partial';
        return 'pending';
    }
}

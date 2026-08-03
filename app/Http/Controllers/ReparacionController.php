<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DeviceBrand;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\RepairService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReparacionController extends Controller
{
    public function getServices()
    {
        try {
            $services = RepairService::active()->orderBy('name')->get(['id', 'name', 'description', 'price']);

            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function storeService(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|unique:repair_services,name',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $service = RepairService::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'is_active' => true,
            ]);

            return response()->json($service, 201);
        } catch (\Exception $e) {
            \Log::error('Error creating repair service: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al crear el servicio en la base de datos',
            ], 500);
        }
    }

    private function nextOrderNumber(): string
    {
        $max = (int) RepairOrder::query()
            ->whereNotNull('order_number')
            ->where('order_number', 'like', 'REP-%')
            ->selectRaw('MAX(CAST(SUBSTRING(order_number, 5) AS UNSIGNED)) as max_num')
            ->value('max_num');

        return 'REP-'.str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    private function resolveLockType(Request $request, ?RepairOrder $order = null): string
    {
        $requestedType = $request->input('lock_type');

        if ($requestedType && in_array($requestedType, ['password', 'pattern', 'none'], true)) {
            return $requestedType;
        }

        if ($order?->lock_type) {
            return $order->lock_type;
        }

        $value = (string) ($order?->device_password ?? '');
        if ($value === '') {
            return 'none';
        }

        return preg_match('/^[1-9](?:-[1-9])*$/', $value) ? 'pattern' : 'password';
    }

    private function normalizeLockData(string $lockType, ?string $devicePassword): array
    {
        $value = trim((string) ($devicePassword ?? ''));

        if ($lockType === 'password') {
            return [
                'lock_type' => 'password',
                'device_password' => $value !== '' ? $value : null,
            ];
        }

        if ($lockType === 'pattern') {
            return [
                'lock_type' => 'pattern',
                'device_password' => $value !== '' ? $value : null,
            ];
        }

        return [
            'lock_type' => 'none',
            'device_password' => null,
        ];
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
            'total' => RepairOrder::count(),
            'received' => RepairOrder::where('status', 'received')->count(),
            'in_repair' => RepairOrder::whereIn('status', ['diagnosing', 'waiting_parts', 'in_repair'])->count(),
            'ready' => RepairOrder::where('status', 'ready')->count(),
            'delivered' => RepairOrder::where('status', 'delivered')->count(),
        ];

        return view('reparaciones.index', compact('orders', 'stats'));
    }

    public function create()
    {
        $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
        $technicians = User::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'code', 'sale_price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Try to get brands from database, fallback to default list if table doesn't exist
        try {
            $brands = DeviceBrand::select('id', 'name')->active()->orderBy('name')->get();
        } catch (\Exception $e) {
            // Fallback to default brands if table doesn't exist
            $brands = collect([
                (object) ['id' => 1, 'name' => 'Samsung'],
                (object) ['id' => 2, 'name' => 'Apple'],
                (object) ['id' => 3, 'name' => 'Xiaomi'],
                (object) ['id' => 4, 'name' => 'Huawei'],
                (object) ['id' => 5, 'name' => 'Motorola'],
                (object) ['id' => 6, 'name' => 'LG'],
                (object) ['id' => 7, 'name' => 'Sony'],
                (object) ['id' => 8, 'name' => 'Nokia'],
                (object) ['id' => 9, 'name' => 'OPPO'],
                (object) ['id' => 10, 'name' => 'Realme'],
                (object) ['id' => 11, 'name' => 'OnePlus'],
                (object) ['id' => 12, 'name' => 'Tecno'],
                (object) ['id' => 13, 'name' => 'ZTE'],
            ]);
        }

        // Try to get services from database
        try {
            $services = RepairService::active()->orderBy('name')->get();
        } catch (\Exception $e) {
            $services = collect();
        }

        return view('reparaciones.create', compact('clients', 'technicians', 'products', 'brands', 'services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'client_name' => 'required|string|max:150',
            'client_phone' => 'nullable|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'device_brand' => 'required|string|max:60',
            'device_model' => 'required|string|max:100',
            'device_color' => 'nullable|string|max:50',
            'device_imei' => 'nullable|string|max:60',
            'lock_type' => 'nullable|in:password,pattern,none',
            'device_password' => 'nullable|string|max:100',
            'accessories' => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis' => 'nullable|string',
            'repair_notes' => 'nullable|string',
            'status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'technician_id' => 'nullable|exists:users,id',
            'received_date' => 'required|date',
            'received_time' => 'required|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => 'nullable|exists:repair_services,id',
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
        ]);

        $lockData = $this->normalizeLockData(
            $this->resolveLockType($request),
            $validated['device_password'] ?? null
        );
        $validated['lock_type'] = $lockData['lock_type'];
        $validated['device_password'] = $lockData['device_password'];

        $order = null;

        DB::transaction(function () use ($validated, $request, &$order) {
            $items = $validated['items'] ?? [];
            $partsCost = array_sum(array_map(fn ($i) => ($i['quantity'] ?? 0) * ($i['price'] ?? 0), $items));
            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $discountPct = (float) ($validated['discount_percentage'] ?? 0);
            $discountFixed = (float) ($validated['discount_amount'] ?? 0);

            $subtotal = $laborCost + $partsCost;
            $percentageDiscount = $subtotal * ($discountPct / 100);
            $totalDiscount = $percentageDiscount + $discountFixed;
            $total = $subtotal - $totalDiscount;

            $order = RepairOrder::create([
                'order_number' => $this->nextOrderNumber(),
                'client_id' => $validated['client_id'] ?? null,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'device_brand' => $validated['device_brand'],
                'device_model' => $validated['device_model'],
                'device_color' => $validated['device_color'] ?? null,
                'device_imei' => $validated['device_imei'] ?? null,
                'device_password' => $validated['device_password'] ?? null,
                'lock_type' => $validated['lock_type'] ?? 'none',
                'accessories' => $validated['accessories'] ?? null,
                'problem_description' => $validated['problem_description'],
                'diagnosis' => $validated['diagnosis'] ?? null,
                'repair_notes' => $validated['repair_notes'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'technician_id' => $validated['technician_id'] ?? null,
                'user_id' => $request->user()?->id ?? 1,
                'received_date' => $validated['received_date'],
                'received_time' => $validated['received_time'],
                'estimated_date' => $validated['estimated_date'] ?? null,
                'delivered_date' => $validated['status'] === 'delivered' ? now()->toDateString() : null,
                'delivered_time' => $validated['status'] === 'delivered' ? now()->format('H:i:s') : null,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'total' => $subtotal,
                'discount_percentage' => $discountPct,
                'discount_amount' => $discountFixed,
                'advance_payment' => (float) ($validated['advance_payment'] ?? 0),
                'payment_type' => $validated['payment_type'],
                'payment_status' => $this->calcPaymentStatus($total, (float) ($validated['advance_payment'] ?? 0)),
            ]);

            foreach ($items as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['price'];
                RepairOrderItem::create([
                    'repair_order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'service_id' => $item['service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                    'item_type' => $item['item_type'] ?? 'part',
                    'device_brand' => $item['device_brand'] ?? null,
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
        $order = RepairOrder::with('items.product')->findOrFail($id);
        $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
        $technicians = User::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'code', 'sale_price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Try to get brands from database, fallback to default list if table doesn't exist
        try {
            $brands = DeviceBrand::select('id', 'name')->active()->orderBy('name')->get();
        } catch (\Exception $e) {
            // Fallback to default brands if table doesn't exist
            $brands = collect([
                (object) ['id' => 1, 'name' => 'Samsung'],
                (object) ['id' => 2, 'name' => 'Apple'],
                (object) ['id' => 3, 'name' => 'Xiaomi'],
                (object) ['id' => 4, 'name' => 'Huawei'],
                (object) ['id' => 5, 'name' => 'Motorola'],
                (object) ['id' => 6, 'name' => 'LG'],
                (object) ['id' => 7, 'name' => 'Sony'],
                (object) ['id' => 8, 'name' => 'Nokia'],
                (object) ['id' => 9, 'name' => 'OPPO'],
                (object) ['id' => 10, 'name' => 'Realme'],
                (object) ['id' => 11, 'name' => 'OnePlus'],
                (object) ['id' => 12, 'name' => 'Tecno'],
                (object) ['id' => 13, 'name' => 'ZTE'],
            ]);
        }

        // Try to get services from database
        try {
            $services = RepairService::active()->orderBy('name')->get();
        } catch (\Exception $e) {
            $services = collect();
        }

        return view('reparaciones.edit', compact('order', 'clients', 'technicians', 'products', 'brands', 'services'));
    }

    public function update(Request $request, $id)
    {
        $order = RepairOrder::findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'client_name' => 'required|string|max:150',
            'client_phone' => 'nullable|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'device_brand' => 'required|string|max:60',
            'device_model' => 'required|string|max:100',
            'device_color' => 'nullable|string|max:50',
            'device_imei' => 'nullable|string|max:60',
            'lock_type' => 'nullable|in:password,pattern,none',
            'device_password' => 'nullable|string|max:100',
            'accessories' => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis' => 'nullable|string',
            'repair_notes' => 'nullable|string',
            'status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'technician_id' => 'nullable|exists:users,id',
            'received_date' => 'required|date',
            'received_time' => 'required|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
            'delivered_time' => 'nullable|date_format:H:i',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => 'nullable|exists:repair_services,id',
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
        ]);

        $lockData = $this->normalizeLockData(
            $this->resolveLockType($request, $order),
            $validated['device_password'] ?? null
        );
        $validated['lock_type'] = $lockData['lock_type'];
        $validated['device_password'] = $lockData['device_password'];

        DB::transaction(function () use ($validated, $order) {
            $items = $validated['items'] ?? [];
            $partsCost = array_sum(array_map(fn ($i) => ($i['quantity'] ?? 0) * ($i['price'] ?? 0), $items));
            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $discountPct = (float) ($validated['discount_percentage'] ?? 0);
            $discountFixed = (float) ($validated['discount_amount'] ?? 0);
            $advance = (float) ($validated['advance_payment'] ?? 0);

            $subtotal = $laborCost + $partsCost;
            $percentageDiscount = $subtotal * ($discountPct / 100);
            $totalDiscount = $percentageDiscount + $discountFixed;
            $total = $subtotal - $totalDiscount;

            // Record the exact delivery date and time automatically when needed.
            $deliveredDate = $validated['delivered_date'] ?? null;
            $deliveredTime = $validated['delivered_time'] ?? null;
            if ($validated['status'] === 'delivered') {
                $deliveredDate = $deliveredDate ?: ($order->delivered_date?->toDateString() ?? now()->toDateString());
                $deliveredTime = $deliveredTime ?: ($order->delivered_time ?: now()->format('H:i:s'));
            }

            $order->update([
                'client_id' => $validated['client_id'] ?? null,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'device_brand' => $validated['device_brand'],
                'device_model' => $validated['device_model'],
                'device_color' => $validated['device_color'] ?? null,
                'device_imei' => $validated['device_imei'] ?? null,
                'device_password' => $validated['device_password'] ?? null,
                'lock_type' => $validated['lock_type'] ?? 'none',
                'accessories' => $validated['accessories'] ?? null,
                'problem_description' => $validated['problem_description'],
                'diagnosis' => $validated['diagnosis'] ?? null,
                'repair_notes' => $validated['repair_notes'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'technician_id' => $validated['technician_id'] ?? null,
                'received_date' => $validated['received_date'],
                'received_time' => $validated['received_time'],
                'estimated_date' => $validated['estimated_date'] ?? null,
                'delivered_date' => $deliveredDate,
                'delivered_time' => $deliveredTime,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'total' => $subtotal,
                'advance_payment' => $advance,
                'payment_type' => $validated['payment_type'],
                'payment_status' => $this->calcPaymentStatus($total, $advance),
            ]);

            $order->update([
                'discount_percentage' => $discountPct,
                'discount_amount' => $discountFixed,
            ]);

            $order->items()->delete();

            foreach ($items as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['price'];
                RepairOrderItem::create([
                    'repair_order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'service_id' => $item['service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                    'item_type' => $item['item_type'] ?? 'part',
                    'device_brand' => $item['device_brand'] ?? null,
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
        $order = RepairOrder::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled'])['status'];

        $update = ['status' => $status];
        if ($status === 'delivered' && ! $order->delivered_date) {
            $update['delivered_date'] = now()->toDateString();
        }
        if ($status === 'delivered' && ! $order->delivered_time) {
            $update['delivered_time'] = now()->format('H:i:s');
        }

        $order->update($update);

        return back()->with('success', 'Estado actualizado a: '.$order->fresh()->statusLabel());
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
        if ($total <= 0 || $advance >= $total) {
            return 'paid';
        }
        if ($advance > 0) {
            return 'partial';
        }

        return 'pending';
    }
}

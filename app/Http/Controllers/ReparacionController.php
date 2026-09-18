<?php

namespace App\Http\Controllers;

use App\Models\CajaSession;
use App\Models\Client;
use App\Models\DeviceBrand;
use App\Models\NumberSequence;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\RepairOrderPhoto;
use App\Models\RepairService;
use App\Models\Sale;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReparacionController extends Controller
{
    private function normalizeDiscount(array $validated): array
    {
        $percentage = (float) ($validated['discount_percentage'] ?? 0);
        $fixed = (float) ($validated['discount_amount'] ?? 0);
        $type = $validated['discount_type'] ?? null;

        if (! in_array($type, ['none', 'percentage', 'fixed'], true)) {
            $type = $percentage > 0 ? 'percentage' : ($fixed > 0 ? 'fixed' : 'none');
        }

        if ($percentage > 0 && $fixed > 0) {
            throw ValidationException::withMessages([
                'discount_type' => 'Seleccione un solo tipo de descuento: fijo o porcentaje.',
            ]);
        }

        if ($type === 'percentage' && $fixed > 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'El descuento fijo no se puede combinar con el porcentaje.',
            ]);
        }

        if ($type === 'fixed' && $percentage > 0) {
            throw ValidationException::withMessages([
                'discount_percentage' => 'El porcentaje no se puede combinar con el descuento fijo.',
            ]);
        }

        return match ($type) {
            'percentage' => [$percentage, 0.0],
            'fixed' => [0.0, $fixed],
            default => [0.0, 0.0],
        };
    }

    private function nextOrderNumber(): string
    {
        $minimum = RepairOrder::query()
            ->whereNotNull('order_number')
            ->pluck('order_number')
            ->reduce(function (int $max, mixed $value): int {
                return is_string($value) && preg_match('/^REP-([0-9]+)$/', $value, $matches) === 1
                    ? max($max, (int) $matches[1])
                    : $max;
            }, 0) + 1;

        return NumberSequence::getNextAtLeast('reparacion', $minimum);
    }

    public function index(Request $request)
    {
        $query = RepairOrder::query()->with('technician')->latest();

        $this->applyRepairIndexFilters($query, $request);

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => RepairOrder::count(),
            'received' => RepairOrder::where('status', 'received')->count(),
            'in_repair' => RepairOrder::whereIn('status', ['diagnosing', 'waiting_parts', 'in_repair'])->count(),
            'ready' => RepairOrder::where('status', 'ready')->count(),
            'delivered' => RepairOrder::where('status', 'delivered')->count(),
            'overdue' => RepairOrder::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereNotNull('estimated_date')
                ->whereDate('estimated_date', '<', now()->toDateString())
                ->count(),
            'due_today' => RepairOrder::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereDate('estimated_date', now()->toDateString())
                ->count(),
        ];

        $expenseStats = [
            'month_count' => OperationalExpense::registered()->whereDate('expense_date', '>=', now()->startOfMonth())->count(),
            'month_total' => (float) OperationalExpense::registered()->whereDate('expense_date', '>=', now()->startOfMonth())->sum('amount'),
        ];

        $technicians = User::query()->select('id', 'name')->orderBy('name')->get();

        $deviceBrands = RepairOrder::query()
            ->whereNotNull('device_brand')
            ->where('device_brand', '!=', '')
            ->distinct()
            ->orderBy('device_brand')
            ->pluck('device_brand');

        $filteredCount = $orders->total();

        return view('reparaciones.index', compact(
            'orders',
            'stats',
            'expenseStats',
            'technicians',
            'deviceBrands',
            'filteredCount',
        ));
    }

    private function applyRepairIndexFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where(function ($sq) use ($q) {
                $sq->where('order_number', 'like', "%{$q}%")
                    ->orWhere('client_name', 'like', "%{$q}%")
                    ->orWhere('client_phone', 'like', "%{$q}%")
                    ->orWhere('device_brand', 'like', "%{$q}%")
                    ->orWhere('device_model', 'like', "%{$q}%")
                    ->orWhere('device_imei', 'like', "%{$q}%")
                    ->orWhere('problem_description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->integer('technician_id'));
        }

        if ($request->filled('device_brand')) {
            $query->where('device_brand', $request->string('device_brand')->toString());
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('received_from')) {
            $query->whereDate('received_date', '>=', $request->string('received_from')->toString());
        }

        if ($request->filled('received_to')) {
            $query->whereDate('received_date', '<=', $request->string('received_to')->toString());
        }

        if ($request->filled('delivery_from')) {
            $query->whereDate('estimated_date', '>=', $request->string('delivery_from')->toString());
        }

        if ($request->filled('delivery_to')) {
            $query->whereDate('estimated_date', '<=', $request->string('delivery_to')->toString());
        }

        if ($request->boolean('overdue_only')) {
            $query->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereNotNull('estimated_date')
                ->whereDate('estimated_date', '<', now()->toDateString());
        }

        if ($request->filled('date')) {
            $query->whereDate('received_date', $request->string('date')->toString());
        }
    }

    public function create()
    {
        $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
        $technicians = User::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'code', 'sale_price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $brands = DeviceBrand::select('id', 'name')->active()->orderBy('name')->get();
        $services = RepairService::active()->orderBy('name')->get();

        return view('reparaciones.create', compact('clients', 'technicians', 'products', 'brands', 'services'));
    }

    public function store(Request $request)
    {
        $this->normalizeTimeInputs($request);

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'client_name' => 'required|string|max:150',
            'client_phone' => 'nullable|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'device_brand' => 'required|string|max:60',
            'device_model' => 'required|string|max:100',
            'device_color' => 'nullable|string|max:50',
            'device_imei' => 'nullable|string|max:60',
            'accessories' => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis' => 'nullable|string',
            'repair_notes' => 'nullable|string',
            'status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'technician_id' => 'nullable|exists:users,id',
            'received_date' => 'required|date',
            'received_time' => 'nullable|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable|date_format:H:i',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:none,percentage,fixed',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer',
            'warranty_enabled' => 'nullable|boolean',
            'warranty_text' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => 'nullable|exists:repair_services,id',
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=3000,max_height=3000',
        ], $this->timeValidationMessages());

        // El taller de joyería no solicita ni conserva credenciales del cliente.
        $validated['lock_type'] = 'none';
        $validated['device_password'] = null;
        [$validated['discount_percentage'], $validated['discount_amount']] = $this->normalizeDiscount($validated);

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

            if ($total < 0) {
                throw new \RuntimeException('El descuento total no puede superar el subtotal de la reparación.');
            }

            $orderData = [
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
                'received_time' => $validated['received_time'] ?? now()->format('H:i'),
                'estimated_date' => $validated['estimated_date'] ?? null,
                'estimated_delivery_time' => $validated['estimated_delivery_time'] ?? null,
                'delivered_time' => $validated['status'] === 'delivered' ? now()->format('H:i') : null,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'total' => round($total, 2),
                'discount_percentage' => $discountPct,
                'discount_amount' => $discountFixed,
                'advance_payment' => (float) ($validated['advance_payment'] ?? 0),
                'payment_type' => $validated['payment_type'],
                'payment_status' => $this->calcPaymentStatus($total, (float) ($validated['advance_payment'] ?? 0)),
                'warranty_enabled' => $validated['warranty_enabled'] ?? true,
                'warranty_text' => $validated['warranty_text'] ?? null,
            ];
            if (RepairOrder::supportsPaymentTracking()) {
                $orderData['caja_session_id'] = $validated['payment_type'] === 'cash'
                    ? CajaSession::currentForUser($request->user()?->id)?->id
                    : null;
                $orderData['payment_received_at'] = (float) ($validated['advance_payment'] ?? 0) > 0 ? now() : null;
            }
            $order = RepairOrder::create($orderData);

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

            $this->storePhoto($order, $request->file('photo'));

            if (RepairOrder::supportsPaymentTracking()) {
                app(AccountingService::class)->recordRepairPayment($order->fresh());
            }
        });

        return redirect()->route('reparaciones.show', $order->id)
            ->with('success', 'Orden de reparación creada correctamente.');
    }

    public function show($id)
    {
        $order = RepairOrder::with('items.product', 'photos', 'client', 'technician', 'user')->findOrFail($id);

        return view('reparaciones.show', compact('order'));
    }

    public function edit($id)
    {
        $order = RepairOrder::with('items.product', 'photos')->findOrFail($id);
        $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
        $technicians = User::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'code', 'sale_price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $brands = DeviceBrand::select('id', 'name')->active()->orderBy('name')->get();
        $services = RepairService::active()->orderBy('name')->get();

        return view('reparaciones.edit', compact('order', 'clients', 'technicians', 'products', 'brands', 'services'));
    }

    public function update(Request $request, $id)
    {
        $order = RepairOrder::findOrFail($id);
        $this->normalizeTimeInputs($request);

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'client_name' => 'required|string|max:150',
            'client_phone' => 'nullable|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'device_brand' => 'required|string|max:60',
            'device_model' => 'required|string|max:100',
            'device_color' => 'nullable|string|max:50',
            'device_imei' => 'nullable|string|max:60',
            'accessories' => 'nullable|string',
            'problem_description' => 'required|string',
            'diagnosis' => 'nullable|string',
            'repair_notes' => 'nullable|string',
            'status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'technician_id' => 'nullable|exists:users,id',
            'received_date' => 'required|date',
            'received_time' => 'nullable|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable|date_format:H:i',
            'delivered_date' => 'nullable|date',
            'delivered_time' => 'nullable|date_format:H:i',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:none,percentage,fixed',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer',
            'warranty_enabled' => 'nullable|boolean',
            'warranty_text' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => 'nullable|exists:repair_services,id',
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=3000,max_height=3000',
        ], $this->timeValidationMessages());

        if ($request->hasFile('photo') && $order->photos()->exists()) {
            throw ValidationException::withMessages(['photo' => 'Elimina la foto actual antes de agregar otra.']);
        }

        // Elimina credenciales heredadas de antiguas órdenes de celulares al editarlas.
        $validated['lock_type'] = 'none';
        $validated['device_password'] = null;
        [$validated['discount_percentage'], $validated['discount_amount']] = $this->normalizeDiscount($validated);

        DB::transaction(function () use ($validated, $order, $request) {
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

            if ($total < 0) {
                throw new \RuntimeException('El descuento total no puede superar el subtotal de la reparación.');
            }

            if ($validated['status'] === 'delivered') {
                $advance = $total;
            }

            $paymentTrackingEnabled = RepairOrder::supportsPaymentTracking();
            $financialDataChanged = $paymentTrackingEnabled && ((float) $order->advance_payment !== $advance
                || $order->payment_type !== $validated['payment_type']
                || $order->status === 'cancelled'
                || $validated['status'] === 'cancelled');
            if ($financialDataChanged) {
                app(AccountingService::class)->voidForSource(RepairOrder::class, $order->id, 'Cobro de taller actualizado o anulado.');
            }

            // Mark delivered_date automatically
            $deliveredDate = $validated['delivered_date'] ?? null;
            $deliveredTime = $validated['delivered_time'] ?? null;
            if ($validated['status'] === 'delivered') {
                if (! $deliveredDate && ! $order->delivered_date) {
                    $deliveredDate = now()->toDateString();
                }
                if (! $deliveredTime && ! $order->delivered_time) {
                    $deliveredTime = now()->format('H:i');
                }
                $validated['device_password'] = null;
                $validated['lock_type'] = 'none';
            }

            $orderData = [
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
                'received_time' => $validated['received_time'] ?? ($order->received_time ?? now()->format('H:i')),
                'estimated_date' => $validated['estimated_date'] ?? null,
                'estimated_delivery_time' => $validated['estimated_delivery_time'] ?? null,
                'delivered_date' => $deliveredDate,
                'delivered_time' => $deliveredTime,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'total' => round($total, 2),
                'advance_payment' => $advance,
                'payment_type' => $validated['payment_type'],
                'payment_status' => $this->calcPaymentStatus($total, $advance),
                'warranty_enabled' => $validated['warranty_enabled'] ?? false,
                'warranty_text' => $validated['warranty_text'] ?? null,
            ];
            if ($paymentTrackingEnabled) {
                $orderData['caja_session_id'] = $validated['payment_type'] === 'cash'
                    ? ($order->caja_session_id ?? CajaSession::currentForUser($order->user_id)?->id)
                    : null;
                $orderData['payment_received_at'] = $financialDataChanged && $advance > 0 ? now() : ($advance > 0 ? $order->payment_received_at : null);
            }
            $order->update($orderData);

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

            $this->storePhoto($order, $request->file('photo'));

            if ($financialDataChanged) {
                app(AccountingService::class)->recordRepairPayment($order->fresh());
            }
            $this->syncWorkshopInvoice($order->fresh());
        });

        return redirect()->route('reparaciones.show', $order->id)
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy($id)
    {
        $order = RepairOrder::with('photos')->findOrFail($id);
        foreach ($order->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        $order->items()->delete();
        $order->delete();

        return redirect()->route('reparaciones.index')
            ->with('success', 'Orden eliminada.');
    }

    public function destroyPhoto(RepairOrderPhoto $photo)
    {
        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Foto eliminada.');
    }

    public function showPhoto(RepairOrderPhoto $photo)
    {
        abort_unless(Storage::disk('public')->exists($photo->path), 404);

        return Storage::disk('public')->response($photo->path);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = RepairOrder::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,cancelled'])['status'];

        $update = ['status' => $status];
        $paymentTrackingEnabled = RepairOrder::supportsPaymentTracking();
        $shouldRecordPayment = $paymentTrackingEnabled && $status === 'delivered' && (float) $order->advance_payment < (float) $order->total;
        if ($status === 'delivered') {
            if (! $order->delivered_date) {
                $update['delivered_date'] = now()->toDateString();
            }
            if (! $order->delivered_time) {
                $update['delivered_time'] = now()->format('H:i');
            }
            if ($shouldRecordPayment) {
                $update['advance_payment'] = $order->total;
                $update['payment_status'] = 'paid';
                $update['payment_received_at'] = now();
                $update['caja_session_id'] = $order->payment_type === 'cash'
                    ? ($order->caja_session_id ?? CajaSession::currentForUser($order->user_id)?->id)
                    : null;
            }
        }

        DB::transaction(function () use ($order, $update, $shouldRecordPayment): void {
            if ($shouldRecordPayment) {
                app(AccountingService::class)->voidForSource(RepairOrder::class, $order->id, 'Cobro de taller actualizado al entregar la orden.');
            }

            $order->update($update);

            if ($shouldRecordPayment) {
                app(AccountingService::class)->recordRepairPayment($order->fresh());
            }

            $this->syncWorkshopInvoice($order->fresh());
        });

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

    private function normalizeTimeInputs(Request $request): void
    {
        foreach (['received_time', 'estimated_delivery_time', 'delivered_time'] as $field) {
            $value = $request->input($field);

            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                $request->merge([$field => substr($value, 0, 5)]);
            }
        }
    }

    private function timeValidationMessages(): array
    {
        return [
            'received_time.date_format' => 'La hora de recepción debe tener el formato HH:MM.',
            'estimated_delivery_time.date_format' => 'La hora estimada de entrega debe tener el formato HH:MM.',
            'delivered_time.date_format' => 'La hora de entrega debe tener el formato HH:MM.',
        ];
    }

    private function storePhoto(RepairOrder $order, ?UploadedFile $photo): void
    {
        if (! $photo) {
            return;
        }

        $source = imagecreatefromstring(file_get_contents($photo->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['photo' => 'No fue posible procesar la imagen.']);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1200 / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $optimized = imagecreatetruecolor($width, $height);
        imagealphablending($optimized, false);
        imagesavealpha($optimized, true);
        imagecopyresampled($optimized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        $path = 'repair-orders/'.$order->id.'/'.uniqid('photo_', true).'.webp';
        Storage::disk('public')->makeDirectory('repair-orders/'.$order->id);
        imagewebp($optimized, Storage::disk('public')->path($path), 78);
        imagedestroy($optimized);
        imagedestroy($source);

        $order->photos()->create(['path' => $path]);
    }

    private function syncWorkshopInvoice(RepairOrder $order): void
    {
        if ($order->status !== 'delivered' || (float) $order->advance_payment < (float) $order->total) {
            return;
        }

        $client = $order->client ?? Client::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'Cliente genérico', 'phone' => 'N/A']
        );
        $cashSession = $order->payment_type === 'cash'
            ? ($order->cajaSession ?? CajaSession::currentForUser($order->user_id))
            : null;

        Sale::updateOrCreate(
            ['repair_order_id' => $order->id],
            [
                'invoice_number' => Sale::where('repair_order_id', $order->id)->value('invoice_number') ?? NumberSequence::getNext('factura'),
                'client_id' => $client->id,
                'user_id' => $order->user_id,
                'branch_id' => $cashSession?->branch_id,
                'caja_session_id' => $cashSession?->id,
                'billing_name' => $order->client_name,
                'billing_phone' => $order->client_phone,
                'billing_email' => $order->client_email,
                'date' => $order->payment_received_at ?? now(),
                'subtotal' => $order->total,
                'tax_total' => 0,
                'total' => $order->total,
                'amount_paid' => $order->advance_payment,
                'payment_type' => $order->payment_type,
                'tax_included' => false,
                'tax_rate' => 0,
                'status' => 'completed',
                'notes' => 'Taller de reparación · Orden '.$order->order_number,
            ],
        );
    }
}

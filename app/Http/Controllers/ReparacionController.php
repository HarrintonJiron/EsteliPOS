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
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CompanySettingsService;
use App\Services\CreditService;
use App\Services\InventoryService;
use App\Services\MoneyDisplayService;
use App\Services\RepairPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReparacionController extends Controller
{
    private const UNPAID_SQL = 'total - advance_payment - COALESCE((SELECT SUM(amount) FROM repair_credit_payments WHERE repair_credit_payments.repair_order_id = repair_orders.id), 0) > 0.00001';

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AccountingService $accountingService,
    ) {}

    protected function workshopType(): string
    {
        return 'repair';
    }

    protected function workshopRoutePrefix(): string
    {
        return 'reparaciones';
    }

    private function workshopQuery()
    {
        return RepairOrder::query()->where('order_type', $this->workshopType());
    }

    private function findWorkshopOrder(int|string $id): RepairOrder
    {
        return $this->workshopQuery()->findOrFail($id);
    }

    private function workshopViewData(): array
    {
        $isJewelry = $this->workshopType() === 'jewelry';

        return [
            'routePrefix' => $this->workshopRoutePrefix(),
            'isJewelry' => $isJewelry,
            'workshopName' => $isJewelry ? 'Joyería' : 'Reparaciones',
            'itemName' => $isJewelry ? 'pieza' : 'equipo',
            'catalogTypeIndexRoute' => $isJewelry ? 'joyeria.catalogs.types.index' : 'device-brands.index',
            'catalogTypeStoreRoute' => $isJewelry ? 'joyeria.catalogs.types.store' : 'device-brands.store',
            'catalogServiceStoreRoute' => $isJewelry ? 'joyeria.catalogs.services.store' : 'repair-services.store',
            'workshopStatusLabels' => $isJewelry
                ? ['received' => 'Recibida', 'diagnosing' => 'En evaluación', 'waiting_parts' => 'Esperando materiales', 'in_repair' => 'En taller', 'ready' => 'Lista para entregar', 'delivered' => 'Entregada', 'cancelled' => 'Cancelada']
                : [],
        ];
    }

    private function nextOrderNumber(): string
    {
        $type = $this->workshopType() === 'jewelry' ? 'joyeria' : 'reparacion';
        $prefix = $type === 'joyeria' ? 'JOY' : 'REP';
        $minimum = $this->workshopQuery()
            ->whereNotNull('order_number')
            ->pluck('order_number')
            ->reduce(function (int $max, mixed $value) use ($prefix): int {
                return is_string($value) && preg_match("/^{$prefix}-([0-9]+)$/", $value, $matches) === 1
                    ? max($max, (int) $matches[1])
                    : $max;
            }, 0) + 1;

        return NumberSequence::getNextAtLeast($type, $minimum);
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
        $query = $this->workshopQuery()->with('technician')->latest();

        $this->applyRepairIndexFilters($query, $request);

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => $this->workshopQuery()->count(),
            'received' => $this->workshopQuery()->where('status', 'received')->count(),
            'in_repair' => $this->workshopQuery()->whereIn('status', ['diagnosing', 'waiting_parts', 'in_repair'])->count(),
            'ready' => $this->workshopQuery()->where('status', 'ready')->count(),
            'delivered' => $this->workshopQuery()->where('status', 'delivered')->count(),
            'overdue' => $this->workshopQuery()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereNotNull('estimated_date')
                ->whereDate('estimated_date', '<', now()->toDateString())
                ->count(),
            'due_today' => $this->workshopQuery()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereDate('estimated_date', now()->toDateString())
                ->count(),
        ];

        $expenseStats = [
            'month_count' => OperationalExpense::registered()->whereDate('expense_date', '>=', now()->startOfMonth())->count(),
            'month_total' => (float) OperationalExpense::registered()->whereDate('expense_date', '>=', now()->startOfMonth())->sum('amount'),
        ];

        $technicians = User::query()->select('id', 'name')->orderBy('name')->get();

        $deviceBrands = $this->workshopQuery()
            ->whereNotNull('device_brand')
            ->where('device_brand', '!=', '')
            ->distinct()
            ->orderBy('device_brand')
            ->pluck('device_brand');

        $filteredCount = $orders->total();

        return view('reparaciones.index', array_merge(compact(
            'orders',
            'stats',
            'expenseStats',
            'technicians',
            'deviceBrands',
            'filteredCount',
        ), $this->workshopViewData()));
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
            $paymentStatus = $request->string('payment_status')->toString();
            if ($paymentStatus === 'delivered_unpaid') {
                $query->where('status', 'delivered')->whereRaw(self::UNPAID_SQL);
            } elseif ($paymentStatus === 'credit') {
                $query->where('payment_type', 'credit')->whereRaw(self::UNPAID_SQL);
            } else {
                $query->where('payment_status', $paymentStatus);
            }
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

        $brands = DeviceBrand::select('id', 'name')->forWorkshop($this->workshopType())->active()->orderBy('name')->get();
        $services = RepairService::forWorkshop($this->workshopType())->active()->orderBy('name')->get();

        return view('reparaciones.create', array_merge(compact('clients', 'technicians', 'products', 'brands', 'services'), $this->workshopViewData()));
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
            'device_battery' => 'nullable|integer|min:0|max:100',
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
            'received_time' => 'nullable|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable|date_format:H:i',
            'due_date' => 'nullable|required_if:payment_type,credit|date|after_or_equal:received_date',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer,credit',
            'warranty_enabled' => 'nullable|boolean',
            'warranty_text' => 'nullable|string|max:2000',
            'include_warranty_policy' => 'nullable|boolean',
            'warranty_days' => 'nullable|integer|min:1|max:3650',
            'warranty_policy' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => ['nullable', Rule::exists('repair_services', 'id')->where('workshop_type', $this->workshopType())],
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
            'photos' => 'nullable|array|max:'.RepairOrder::MAX_PHOTOS,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
            'device_photos' => 'nullable|array|max:'.RepairOrder::MAX_PHOTOS,
            'device_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
        ], $this->timeValidationMessages());

        $legacyPhoto = $request->file('photo');
        if (count($request->file('photos', [])) + count($request->file('device_photos', [])) + ($legacyPhoto ? 1 : 0) > RepairOrder::MAX_PHOTOS) {
            throw ValidationException::withMessages(['device_photos' => 'Cada orden admite hasta '.RepairOrder::MAX_PHOTOS.' fotografías.']);
        }

        $this->ensureSingleDiscountType($validated);
        $this->ensureValidWorkshopAmounts($validated);

        $lockData = $this->workshopType() === 'jewelry'
            ? ['lock_type' => 'none', 'device_password' => null]
            : $this->normalizeLockData(
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

            $this->ensureRepairCreditAvailable($validated, round($total, 2));

            if ($total < 0) {
                throw new \RuntimeException('El descuento total no puede superar el subtotal de la reparación.');
            }

            $orderData = [
                'order_number' => $this->nextOrderNumber(),
                'order_type' => $this->workshopType(),
                'client_id' => $validated['client_id'] ?? null,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'device_brand' => $validated['device_brand'],
                'device_model' => $validated['device_model'],
                'device_color' => $validated['device_color'] ?? null,
                'device_imei' => $validated['device_imei'] ?? null,
                'device_battery' => $validated['device_battery'] ?? null,
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
                'estimated_time' => $validated['estimated_delivery_time'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
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
                'include_warranty_policy' => $validated['warranty_enabled'] ?? false,
                'warranty_days' => $validated['warranty_days'] ?? null,
                'warranty_policy' => $validated['warranty_text'] ?? null,
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

            $photos = [...$request->file('photos', []), ...$request->file('device_photos', [])];
            if ($request->file('photo')) {
                $photos[] = $request->file('photo');
            }
            $this->storePhotos($order, $photos);

            if (RepairOrder::supportsPaymentTracking()) {
                app(AccountingService::class)->recordRepairPayment($order->fresh());
            }
        });

        return redirect()->route($this->workshopRoutePrefix().'.show', $order->id)
            ->with('success', 'Orden creada correctamente.');
    }

    public function show($id)
    {
        $order = $this->workshopQuery()->with('items.product', 'photos', 'client', 'technician', 'user', 'creditPayments')->findOrFail($id);
        $creditClients = collect();
        $defaultDueDate = now()->addDays(30)->toDateString();
        if ($order->canCollect() && $order->payment_type !== 'credit') {
            if ($order->client) {
                $defaultDueDate = app(CreditService::class)->dueDateForClient($order->client);
            } else {
                $creditClients = Client::query()->where('credit_enabled', true)->orderBy('name')->get(['id', 'name', 'phone', 'credit_days']);
            }
        }

        return view('reparaciones.show', array_merge(compact('order', 'creditClients', 'defaultDueDate'), $this->workshopViewData()));
    }

    public function edit($id)
    {
        $order = $this->workshopQuery()->with('items.product', 'photos')->findOrFail($id);
        $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
        $technicians = User::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'code', 'sale_price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $brands = DeviceBrand::select('id', 'name')->forWorkshop($this->workshopType())->active()->orderBy('name')->get();
        $services = RepairService::forWorkshop($this->workshopType())->active()->orderBy('name')->get();

        return view('reparaciones.edit', array_merge(compact('order', 'clients', 'technicians', 'products', 'brands', 'services'), $this->workshopViewData()));
    }

    public function update(Request $request, $id)
    {
        $order = $this->findWorkshopOrder($id);
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
            'device_battery' => 'nullable|integer|min:0|max:100',
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
            'received_time' => 'nullable|date_format:H:i',
            'estimated_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable|date_format:H:i',
            'delivered_date' => 'nullable|date',
            'delivered_time' => 'nullable|date_format:H:i',
            'due_date' => 'nullable|required_if:payment_type,credit|date|after_or_equal:received_date',
            'delivery_notes' => 'nullable|string|max:2000',
            'labor_cost' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,card,transfer,credit',
            'warranty_enabled' => 'nullable|boolean',
            'warranty_text' => 'nullable|string|max:2000',
            'include_warranty_policy' => 'nullable|boolean',
            'warranty_days' => 'nullable|integer|min:1|max:3650',
            'warranty_policy' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:200',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.service_id' => ['nullable', Rule::exists('repair_services', 'id')->where('workshop_type', $this->workshopType())],
            'items.*.item_type' => 'nullable|in:part,service',
            'items.*.device_brand' => 'nullable|string|max:60',
            'photos' => 'nullable|array|max:'.RepairOrder::MAX_PHOTOS,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
            'device_photos' => 'nullable|array|max:'.RepairOrder::MAX_PHOTOS,
            'device_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=5000,max_height=5000',
            'remove_photo_ids' => 'nullable|array',
            'remove_photo_ids.*' => 'integer',
        ], $this->timeValidationMessages());

        $this->ensureSingleDiscountType($validated);
        $this->ensureValidWorkshopAmounts($validated);

        $removePhotoIds = array_map('intval', $validated['remove_photo_ids'] ?? []);
        $newPhotos = [...$request->file('photos', []), ...$request->file('device_photos', [])];
        $keptPhotos = $order->photos()->whereNotIn('id', $removePhotoIds)->count();
        if ($keptPhotos + count($newPhotos) > RepairOrder::MAX_PHOTOS) {
            throw ValidationException::withMessages([
                'device_photos' => 'Cada orden admite hasta '.RepairOrder::MAX_PHOTOS.' fotografías. Elimina alguna antes de agregar más.',
            ]);
        }

        $lockData = $this->workshopType() === 'jewelry'
            ? ['lock_type' => 'none', 'device_password' => null]
            : $this->normalizeLockData(
                $this->resolveLockType($request, $order),
                $validated['device_password'] ?? null
            );
        $validated['lock_type'] = $lockData['lock_type'];
        $validated['device_password'] = $lockData['device_password'];

        DB::transaction(function () use ($validated, $order, $removePhotoIds, $newPhotos) {
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

            if ($validated['status'] === 'delivered' && $validated['payment_type'] !== 'credit') {
                $advance = $total;
            }

            $this->ensureRepairCreditAvailable($validated, round($total, 2), $order);

            if ($advance + $order->paymentsTotal() > round($total, 2) + 0.00001) {
                throw ValidationException::withMessages([
                    'advance_payment' => 'El total no puede ser menor que los pagos ya recibidos.',
                ]);
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
                'device_battery' => $validated['device_battery'] ?? null,
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
                'estimated_time' => $validated['estimated_delivery_time'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'delivery_notes' => filled($validated['delivery_notes'] ?? null) ? trim($validated['delivery_notes']) : null,
                'delivered_date' => $deliveredDate,
                'delivered_time' => $deliveredTime,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'total' => round($total, 2),
                'advance_payment' => $advance,
                'payment_type' => $validated['payment_type'],
                'payment_status' => RepairOrder::paymentStatusFor($total, $advance + $order->paymentsTotal()),
                'warranty_enabled' => $validated['warranty_enabled'] ?? false,
                'warranty_text' => $validated['warranty_text'] ?? null,
                'include_warranty_policy' => $validated['warranty_enabled'] ?? false,
                'warranty_days' => $validated['warranty_days'] ?? null,
                'warranty_policy' => $validated['warranty_text'] ?? null,
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

            $order->photos()->whereIn('id', $removePhotoIds)->get()->each(function (RepairOrderPhoto $photo): void {
                Storage::disk('public')->delete($photo->photo_path);
                $photo->delete();
            });
            $this->storePhotos($order, $newPhotos);

            if ($financialDataChanged) {
                app(AccountingService::class)->recordRepairPayment($order->fresh());
            }
            $this->syncWorkshopInvoice($order->fresh());
        });

        return redirect()->route($this->workshopRoutePrefix().'.show', $order->id)
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * A repair may use a percentage or a fixed discount, never both.
     */
    private function ensureRepairCreditAvailable(array $validated, float $total, ?RepairOrder $existing = null): void
    {
        if (($validated['payment_type'] ?? null) !== 'credit') {
            return;
        }

        $clientId = $validated['client_id'] ?? null;
        if (! $clientId) {
            throw ValidationException::withMessages([
                'client_id' => 'Selecciona un cliente registrado para conceder crédito a la reparación.',
            ]);
        }

        $client = Client::query()->lockForUpdate()->findOrFail($clientId);
        if (! $client->credit_enabled || $client->status !== 'active') {
            throw ValidationException::withMessages([
                'client_id' => 'El cliente debe estar activo y tener crédito habilitado.',
            ]);
        }

        $paymentsTotal = $existing?->paymentsTotal() ?? 0;
        if ($paymentsTotal > 0.00001 && $existing->client_id && (int) $existing->client_id !== (int) $clientId) {
            throw ValidationException::withMessages([
                'client_id' => 'No se puede cambiar el cliente de una reparación que ya tiene abonos.',
            ]);
        }

        $newBalance = max(0, round($total - (float) ($validated['advance_payment'] ?? 0) - $paymentsTotal, 2));
        $previousBalance = $existing && $existing->payment_type === 'credit' && (int) $existing->client_id === (int) $clientId
            ? $existing->balance() : 0;
        $available = app(CreditService::class)->availableCredit($client);
        if ($newBalance > $available + $previousBalance + 0.00001) {
            throw ValidationException::withMessages([
                'payment_type' => 'El saldo de la reparación supera el crédito disponible del cliente.',
            ]);
        }
    }

    private function ensureSingleDiscountType(array $validated): void
    {
        $percentage = (float) ($validated['discount_percentage'] ?? 0);
        $fixed = (float) ($validated['discount_amount'] ?? 0);

        if ($percentage > 0 && $fixed > 0) {
            throw ValidationException::withMessages([
                'discount_type' => 'Selecciona un descuento porcentual o uno fijo, no ambos.',
            ]);
        }
    }

    /**
     * Reject inconsistent money before changing the order or storing files.
     */
    private function ensureValidWorkshopAmounts(array $validated): void
    {
        $itemsTotal = array_sum(array_map(
            fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['price'] ?? 0),
            $validated['items'] ?? []
        ));
        $subtotal = (float) ($validated['labor_cost'] ?? 0) + $itemsTotal;
        $discount = $subtotal * ((float) ($validated['discount_percentage'] ?? 0) / 100)
            + (float) ($validated['discount_amount'] ?? 0);
        $total = round($subtotal - $discount, 2);
        $advance = round((float) ($validated['advance_payment'] ?? 0), 2);

        if ($total < 0) {
            throw ValidationException::withMessages([
                'repair' => 'El descuento total no puede superar el subtotal del trabajo.',
            ]);
        }

        if ($advance > $total) {
            throw ValidationException::withMessages([
                'repair' => 'El anticipo no puede superar el total del trabajo.',
            ]);
        }
    }

    public function destroy($id)
    {
        $order = $this->workshopQuery()->with('photos')->findOrFail($id);
        foreach ($order->photos as $photo) {
            Storage::disk('public')->delete($photo->photo_path);
        }
        $order->items()->delete();
        $order->delete();

        return redirect()->route($this->workshopRoutePrefix().'.index')
            ->with('success', 'Orden eliminada.');
    }

    public function destroyPhoto(RepairOrderPhoto $photo)
    {
        abort_unless($photo->repairOrder?->order_type === $this->workshopType(), 404);
        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return back()->with('success', 'Foto eliminada.');
    }

    public function showPhoto(RepairOrderPhoto $photo)
    {
        abort_unless($photo->repairOrder?->order_type === $this->workshopType(), 404);
        abort_unless(Storage::disk('public')->exists($photo->photo_path), 404);

        return Storage::disk('public')->response($photo->photo_path);
    }

    public function photo($id)
    {
        $order = $this->findWorkshopOrder($id);
        $path = $order->getRawOriginal('device_photo');

        abort_unless($path && $path === 'repair-orders/'.basename($path) && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function orderPhoto(int $id, int $photo)
    {
        $order = $this->findWorkshopOrder($id);
        $record = $order->photos()->findOrFail($photo);
        $path = $record->photo_path;

        abort_unless(preg_match('#^repair-orders/(?:[0-9]+/)?[^/]+$#', $path) === 1 && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function weeklyReport(Request $request)
    {
        $reference = $request->filled('week') ? Carbon::parse($request->string('week')->toString()) : now();
        $weekStart = $reference->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $reference->copy()->endOfWeek(Carbon::SUNDAY);
        $orders = $this->workshopQuery()->with('technician')->withSum('creditPayments', 'amount')
            ->where('status', 'delivered')->whereBetween('delivered_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('delivered_date')->orderBy('delivered_time')->get();
        $pendingInWindow = $this->workshopQuery()->whereNotIn('status', ['delivered', 'cancelled'])
            ->whereBetween('received_date', [$weekStart->toDateString(), $weekEnd->toDateString()])->count();
        $summary = [
            'count' => $orders->count(),
            'total' => (float) $orders->sum('total'),
            'labor_total' => (float) $orders->sum('labor_cost'),
            'parts_total' => (float) $orders->sum('parts_cost'),
            'collected_total' => (float) $orders->sum(fn (RepairOrder $order) => $order->paidAmount()),
            'balance_total' => (float) $orders->sum(fn (RepairOrder $order) => $order->balance()),
            'avg_turnaround_days' => $orders->isEmpty() ? null : round($orders->avg(fn (RepairOrder $order) => $order->received_date->diffInDays($order->delivered_date)), 1),
        ];

        return view('reparaciones.informe-semanal', compact('orders', 'summary', 'weekStart', 'weekEnd', 'pendingInWindow'));
    }

    public function storeCreditPayment(Request $request, $id)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.01', 'payment_type' => 'required|in:cash,transfer,check,other', 'reference_number' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000', 'request_token' => 'nullable|uuid']);
        $duplicate = false;
        try {
            DB::transaction(function () use ($id, $data, $request, &$duplicate) {
                $order = $this->workshopQuery()->lockForUpdate()->findOrFail($id);
                if ($order->payment_type !== 'credit' || ! $order->client_id) {
                    throw new \RuntimeException('Esta orden no corresponde a un crédito de cliente.');
                }
                if (! empty($data['request_token']) && $order->creditPayments()
                    ->where('request_token', $data['request_token'])->exists()) {
                    $duplicate = true;

                    return;
                }
                if ((float) $data['amount'] > $order->balance() + 0.00001) {
                    throw new \RuntimeException('El abono supera el saldo pendiente de '.$this->money($order->balance()).'.');
                }
                app(RepairPaymentService::class)->pay($order, (float) $data['amount'], $data['payment_type'], $data['reference_number'] ?? null, $data['notes'] ?? null, $request->user(), $data['request_token'] ?? null);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $duplicate ? 'El abono ya estaba registrado; no se duplicó.' : 'Abono de taller registrado.');
    }

    private function money(float $amount): string
    {
        return app(MoneyDisplayService::class)->format($amount);
    }

    public function collect(Request $request, $id)
    {
        $data = $request->validate([
            'method' => 'required|in:cash,card,transfer,credit', 'amount' => 'nullable|numeric|min:0.01',
            'received' => 'nullable|numeric|min:0', 'reference_number' => 'nullable|string|max:100',
            'client_id' => 'nullable|exists:clients,id', 'due_date' => 'nullable|date|after_or_equal:today',
            'mark_delivered' => 'nullable|boolean',
        ]);
        $message = '';
        try {
            DB::transaction(function () use ($id, $data, $request, &$message) {
                $order = $this->workshopQuery()->lockForUpdate()->findOrFail($id);
                if (! in_array($order->status, ['ready', 'delivered'], true) || $order->balance() <= 0.00001) {
                    throw new \RuntimeException('La orden debe estar lista y tener saldo pendiente para poder cobrarla.');
                }
                $balance = $order->balance();
                if ($data['method'] === 'credit') {
                    if ($order->payment_type === 'credit') {
                        throw new \RuntimeException('Esta orden ya está a crédito; registra un abono.');
                    }
                    $clientId = $order->client_id ?: ($data['client_id'] ?? null);
                    if (! $clientId) {
                        throw new \RuntimeException('Elige un cliente registrado para conceder crédito.');
                    }
                    $client = Client::query()->lockForUpdate()->findOrFail($clientId);
                    if (! app(CreditService::class)->canGrantCredit($client, $balance)) {
                        throw new \RuntimeException('El cliente no tiene crédito habilitado o límite disponible.');
                    }
                    $order->update(['client_id' => $client->id, 'payment_type' => 'credit', 'due_date' => $data['due_date'] ?? app(CreditService::class)->dueDateForClient($client)]);
                    $order->syncPaymentStatus();
                    $message = 'Orden a crédito por '.$this->money($balance).'.';
                } else {
                    $amount = round((float) ($data['amount'] ?? $balance), 2);
                    if ($amount <= 0.00001 || $amount > $balance + 0.00001) {
                        throw new \RuntimeException('El monto no puede superar el saldo de '.$this->money($balance).'.');
                    }
                    $change = null;
                    if ($data['method'] === 'cash' && array_key_exists('received', $data) && $data['received'] !== null) {
                        if ((float) $data['received'] + 0.00001 < $amount) {
                            throw new \RuntimeException('El efectivo recibido es menor al monto a cobrar.');
                        }
                        $change = round((float) $data['received'] - $amount, 2);
                    }
                    app(RepairPaymentService::class)->pay($order, $amount, $data['method'], $data['reference_number'] ?? null, null, $request->user());
                    $remaining = max(0, round($balance - $amount, 2));
                    $message = 'Cobro registrado: '.$this->money($amount).'.'.(($change ?? 0) > 0 ? ' Cambio: '.$this->money($change).'.' : '');
                    $message .= $remaining > 0.00001 ? ' Saldo pendiente: '.$this->money($remaining).'.' : ' La reparación quedó pagada.';
                }
                if (($data['mark_delivered'] ?? false) && $order->status !== 'delivered') {
                    $order->update(['status' => 'delivered', 'delivered_date' => $order->delivered_date ?: now()->toDateString(), 'delivered_time' => $order->delivered_time ?: now()->format('H:i'), 'device_password' => null, 'lock_type' => 'none']);
                    $message .= ' Marcada como entregada.';
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = $this->findWorkshopOrder($id);
        $validated = $request->validate([
            'status' => 'required|in:received,diagnosing,waiting_parts,in_repair,ready,delivered,not_repaired,cancelled',
            'delivery_notes' => 'nullable|string|max:2000',
        ]);
        $status = $validated['status'];

        $update = ['status' => $status];
        if ($status === 'delivered') {
            $deliveryNotes = trim((string) ($validated['delivery_notes'] ?? ''));
            if ($deliveryNotes !== '') {
                $update['delivery_notes'] = $deliveryNotes;
            }
            if (! $order->delivered_date) {
                $update['delivered_date'] = now()->toDateString();
            }
            if (! $order->delivered_time) {
                $update['delivered_time'] = now()->format('H:i');
            }
            $update['device_password'] = null;
            $update['lock_type'] = 'none';
        }

        DB::transaction(function () use ($order, $update): void {
            $order->update($update);
            $this->syncWorkshopInvoice($order->fresh());
        });

        $fresh = $order->fresh();
        $message = 'Estado actualizado a: '.$fresh->statusLabel();
        if ($fresh->isDeliveredWithBalance()) {
            $message .= '. Atención: se entregó con saldo pendiente de '.$this->money($fresh->balance()).'.';
        }

        return back()->with('success', $message);
    }

    public function ticket($id)
    {
        $order = $this->workshopQuery()->with('items.product', 'technician')->findOrFail($id);
        $companyProfile = array_merge(app(CompanySettingsService::class)->get(), [
            'company_name' => Setting::get('company_name', 'Mi Agroservicio'),
            'company_phone' => Setting::get('company_phone', ''),
        ]);

        return view('reparaciones.ticket', array_merge(compact('order', 'companyProfile'), $this->workshopViewData()));
    }

    public function pdf($id)
    {
        $order = $this->workshopQuery()->with('items.product', 'client', 'technician', 'user')->findOrFail($id);
        $companyProfile = array_merge(app(CompanySettingsService::class)->get(), [
            'company_name' => Setting::get('company_name', 'Mi Agroservicio'),
            'company_legal_name' => Setting::get('company_legal_name', ''),
            'company_ruc' => Setting::get('company_ruc', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'company_address' => Setting::get('company_address', ''),
            'company_city' => Setting::get('company_city', ''),
            'company_country' => Setting::get('company_country', ''),
        ]);

        return view('reparaciones.pdf', array_merge(compact('order', 'companyProfile'), $this->workshopViewData()));
    }

    public function bill(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,card,transfer',
            'amount_received' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $sale = null;
        $changeAmount = 0.0;

        try {
            DB::transaction(function () use ($validated, $request, $id, &$sale, &$changeAmount) {
                $order = RepairOrder::query()->lockForUpdate()->findOrFail($id);
                $order->load(['items.product', 'client']);

                if ($order->sale_id) {
                    throw new \RuntimeException('Esta orden ya fue facturada.');
                }

                if (! in_array($order->status, ['ready', 'delivered'], true)) {
                    throw new \RuntimeException('La reparación debe estar lista o entregada antes de facturarla.');
                }

                $netTotal = $order->netTotal();
                if ($netTotal <= 0) {
                    throw new \RuntimeException('El total final de la reparación debe ser mayor que cero.');
                }

                $balance = max(0, $netTotal - (float) $order->advance_payment);
                $amountReceived = (float) ($validated['amount_received'] ?? 0);
                if ($validated['payment_type'] === 'cash' && $amountReceived < $balance) {
                    throw new \RuntimeException('El monto recibido es menor que el saldo pendiente.');
                }

                $storedPaymentType = $validated['payment_type'] === 'card' ? 'transfer' : $validated['payment_type'];
                $client = $order->client ?: Client::firstOrCreate(
                    ['code' => 'GEN'],
                    ['name' => 'Cliente genérico', 'phone' => 'N/A', 'email' => null, 'address' => null]
                );
                $notes = "Factura generada desde reparación {$order->order_number}.";
                if ((float) $order->advance_payment > 0) {
                    $notes .= ' Anticipo registrado: C$ '.number_format((float) $order->advance_payment, 2, '.', '').'.';
                }
                if (filled($validated['reference_number'] ?? null)) {
                    $notes .= ' Referencia de pago: '.$validated['reference_number'].'.';
                }

                $sale = Sale::create([
                    'invoice_number' => NumberSequence::getNext('factura'),
                    'repair_order_id' => $order->id,
                    'client_id' => $client->id,
                    'user_id' => $request->user()?->id ?? 1,
                    'billing_name' => $order->client_name,
                    'billing_business_name' => $client->business_name ?? null,
                    'billing_ruc' => $client->ruc ?? null,
                    'billing_phone' => $order->client_phone,
                    'billing_email' => $order->client_email,
                    'billing_address' => $client->address ?? null,
                    'date' => now(),
                    'payment_type' => $storedPaymentType,
                    'tax_included' => false,
                    'tax_rate' => 0,
                    'subtotal' => $netTotal,
                    'tax_total' => 0,
                    'discount_percentage' => (float) ($order->discount_percentage ?? 0),
                    'discount_amount' => (float) ($order->discount_amount ?? 0),
                    'total' => $netTotal,
                    'status' => 'completed',
                    'notes' => $notes,
                ]);

                $lines = $order->items->map(fn (RepairOrderItem $item) => [
                    'product' => $item->product,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'gross' => (float) $item->subtotal,
                ])->values()->all();

                if ((float) $order->labor_cost > 0) {
                    $lines[] = [
                        'product' => null,
                        'description' => 'Mano de obra - '.$order->device_brand.' '.$order->device_model,
                        'quantity' => 1.0,
                        'gross' => (float) $order->labor_cost,
                    ];
                }

                $linesGross = array_sum(array_column($lines, 'gross'));
                if ($linesGross <= 0) {
                    $lines[] = [
                        'product' => null,
                        'description' => 'Servicio de reparación - '.$order->device_brand.' '.$order->device_model,
                        'quantity' => 1.0,
                        'gross' => (float) $order->total,
                    ];
                    $linesGross = (float) $order->total;
                }

                $allocated = 0.0;
                $lastIndex = array_key_last($lines);
                foreach ($lines as $index => $line) {
                    $quantity = (float) $line['quantity'];
                    if ($line['product'] && floor($quantity) !== $quantity) {
                        throw new \RuntimeException('La cantidad de cada repuesto debe ser un número entero antes de facturar.');
                    }

                    $lineTotal = $index === $lastIndex
                        ? round($netTotal - $allocated, 2)
                        : round($netTotal * ((float) $line['gross'] / $linesGross), 2);
                    $allocated += $lineTotal;

                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'product_id' => $line['product']?->id,
                        'description' => $line['description'],
                        'quantity' => $quantity,
                        'price' => $quantity > 0 ? round($lineTotal / $quantity, 2) : $lineTotal,
                        'subtotal' => $lineTotal,
                        'tax_rate' => 0,
                        'tax_amount' => 0,
                    ]);

                    if ($line['product']) {
                        $this->inventoryService->stockOut(
                            $line['product'],
                            (int) $quantity,
                            'repair_sale:'.$sale->id,
                            'Repuesto facturado en '.$order->order_number,
                            $sale->user_id,
                        );
                    }
                }

                $this->accountingService->recordSale($sale->fresh());

                $order->creditPayments()->create([
                    'client_id' => $order->client_id,
                    'user_id' => $request->user()?->id,
                    'amount' => $balance,
                    'payment_date' => now(),
                    'payment_type' => $validated['payment_type'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => 'Pago final de la factura '.$sale->invoice_number,
                ]);

                $order->update([
                    'sale_id' => $sale->id,
                    'invoiced_at' => now(),
                    'payment_type' => $validated['payment_type'],
                    'payment_status' => 'paid',
                ]);

                $changeAmount = $validated['payment_type'] === 'cash'
                    ? max(0, $amountReceived - $balance)
                    : 0;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reparaciones.show', $id)
            ->with('success', 'Reparación cobrada y factura '.$sale->invoice_number.' generada correctamente.')
            ->with('change_amount', $changeAmount);
    }

    public function invoiceReceipt($id)
    {
        $order = RepairOrder::with('sale.details.product', 'sale.user', 'sale.client')->findOrFail($id);
        abort_unless($order->sale, 404);
        $order->sale->setRelation('repairOrder', $order);

        return view('facturacion.receipt', [
            'sale' => $order->sale,
            'changeAmount' => (float) request()->query('change', 0),
        ]);
    }

    public function invoicePdf($id)
    {
        $order = RepairOrder::with('sale.details.product', 'sale.client')->findOrFail($id);
        abort_unless($order->sale, 404);
        $order->sale->setRelation('repairOrder', $order);

        return view('facturacion.pdf', ['sale' => $order->sale]);
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
        if ($request->filled('estimated_time') && ! $request->filled('estimated_delivery_time')) {
            $request->merge(['estimated_delivery_time' => $request->input('estimated_time')]);
        }

        foreach (['received_time', 'estimated_delivery_time', 'delivered_time'] as $field) {
            $value = $request->input($field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            foreach (['H:i:s.u', 'H:i:s', 'H:i', 'g:i:s A', 'g:i A', 'h:i:s A', 'h:i A'] as $format) {
                try {
                    $normalized = Carbon::createFromFormat($format, trim($value));
                    if ($normalized !== false) {
                        $request->merge([$field => $normalized->format('H:i')]);
                        break;
                    }
                } catch (\Throwable) {
                    // Try the next supported browser/database representation.
                }
            }
        }

        if ($request->has('include_warranty_policy')) {
            $request->merge([
                'warranty_enabled' => $request->boolean('include_warranty_policy'),
                'warranty_text' => $request->input('warranty_policy'),
            ]);
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

    /** @param array<int, UploadedFile> $photos */
    private function storePhotos(RepairOrder $order, array $photos): void
    {
        foreach ($photos as $photo) {
            $this->storePhoto($order, $photo);
        }
    }

    private function storePhoto(RepairOrder $order, UploadedFile $photo): void
    {
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

        $order->photos()->create(['photo_path' => $path]);
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
            ['repair_order_id' => $order->id, 'repair_credit_payment_id' => null],
            [
                'invoice_number' => Sale::where('repair_order_id', $order->id)->whereNull('repair_credit_payment_id')->value('invoice_number') ?? NumberSequence::getNext('factura'),
                'client_id' => $client->id,
                'user_id' => $order->user_id ?? auth()->id() ?? User::query()->where('is_active', true)->value('id'),
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
                'notes' => ($order->order_type === 'jewelry' ? 'Taller de joyería' : 'Taller de reparación').' · Orden '.$order->order_number,
            ],
        );
    }
}

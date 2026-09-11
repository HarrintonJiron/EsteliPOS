<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationPayment;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function hub()
    {
        $user = request()->user();
        $canReservations = $user->isAdmin() || $user->hasPermission('apartados.view');
        $canShipments = $user->isAdmin() || $user->hasPermission('envios.view');

        return view('operaciones-clientes.index', [
            'canReservations' => $canReservations,
            'canShipments' => $canShipments,
            'activeReservations' => $canReservations ? Reservation::where('status', 'active')->count() : 0,
            'reservedValue' => $canReservations ? Reservation::where('status', 'active')->sum('total') : 0,
            'pendingShipments' => $canShipments ? Shipment::whereIn('status', ['pending', 'prepared', 'shipped'])->count() : 0,
            'deliveredShipments' => $canShipments ? Shipment::where('status', 'delivered')->count() : 0,
            'reservations' => $canReservations ? Reservation::with('client')->latest('reserved_at')->limit(5)->get() : collect(),
            'shipments' => $canShipments ? Shipment::with('client')->latest()->limit(5)->get() : collect(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Reservation::with('client', 'items.product')->latest('reserved_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(fn ($q) => $q->where('number', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%")));
        }

        return view('apartados.index', ['reservations' => $query->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        $pricing = app(PricingService::class);
        $retailList = $pricing->resolvePriceList();
        $wholesaleList = $pricing->wholesaleList();
        $specialList = $pricing->specialList();
        $warehouses = Warehouse::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get()
            ->each(function (Product $product) use ($pricing, $retailList, $wholesaleList, $specialList, $warehouses): void {
                $product->setAttribute('reservation_prices', [
                    'retail' => $pricing->resolveUnitPrice($product, $retailList?->id, $product->base_unit_id),
                    'wholesale' => $wholesaleList ? $pricing->resolveUnitPrice($product, $wholesaleList->id, $product->base_unit_id) : $product->effectivePrice(),
                    'special' => $specialList ? $pricing->resolveUnitPrice($product, $specialList->id, $product->base_unit_id) : $product->effectivePrice(),
                ]);
                $product->setAttribute('reservation_price_breaks', [
                    'retail' => $pricing->priceBreaks($product, $retailList?->id, $product->base_unit_id),
                    'wholesale' => $pricing->priceBreaks($product, $wholesaleList?->id, $product->base_unit_id),
                    'special' => $pricing->priceBreaks($product, $specialList?->id, $product->base_unit_id),
                ]);
                $product->setAttribute('reservation_stock', $warehouses->mapWithKeys(fn (Warehouse $warehouse) => [
                    $warehouse->id => [
                        'physical' => $product->stockInWarehouse($warehouse->id),
                        'reserved' => $product->reservedQuantity($warehouse->id),
                        'available' => $product->availableStock($warehouse->id),
                    ],
                ])->all());
            })
            ->filter(fn (Product $product) => collect($product->reservation_stock)->contains(fn (array $stock) => $stock['available'] > 0));

        return view('apartados.create', [
            'clients' => Client::orderBy('name')->get(['id', 'name', 'phone']),
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id', 'warehouse_id' => 'required|exists:warehouses,id',
            'expires_at' => 'nullable|date|after:now', 'deposit' => 'nullable|numeric|min:0', 'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1', 'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price_type' => ['required', Rule::in(['retail', 'wholesale', 'special'])],
        ]);

        try {
            $reservation = DB::transaction(function () use ($data, $request) {
                $pricing = app(PricingService::class);
                $priceListIds = [
                    'retail' => $pricing->resolvePriceList()?->id,
                    'wholesale' => $pricing->wholesaleList()?->id,
                    'special' => $pricing->specialList()?->id,
                ];
                $total = 0;
                $locked = [];
                foreach ($data['items'] as $item) {
                    $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                    $quantity = (float) $item['quantity'];
                    if ($product->availableStock((int) $data['warehouse_id']) + 0.00001 < $quantity) {
                        throw new \RuntimeException("No hay existencia disponible suficiente para {$product->name}.");
                    }
                    $unitPrice = $pricing->resolveUnitPrice($product, $priceListIds[$item['price_type']], $product->base_unit_id, $quantity, $request->user()->branch_id);
                    $locked[] = [$product, $quantity, round($quantity * $unitPrice, 2), $unitPrice];
                    $total += $quantity * $unitPrice;
                }
                if ((float) ($data['deposit'] ?? 0) > $total) {
                    throw new \RuntimeException('El anticipo no puede superar el total del apartado.');
                }

                $reservation = Reservation::create([
                    'number' => NumberSequence::getNext('apartado'), 'client_id' => $data['client_id'],
                    'user_id' => $request->user()->id, 'branch_id' => $request->user()->branch_id,
                    'warehouse_id' => $data['warehouse_id'], 'reserved_at' => now(), 'expires_at' => $data['expires_at'] ?? null,
                    'total' => round($total, 2), 'deposit' => $data['deposit'] ?? 0, 'status' => 'active', 'notes' => $data['notes'] ?? null,
                ]);
                foreach ($locked as [$product, $quantity, $subtotal, $price]) {
                    ReservationItem::create(['reservation_id' => $reservation->id, 'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $price, 'subtotal' => $subtotal]);
                }

                return $reservation;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('apartados.show', $reservation)->with('success', 'Apartado registrado correctamente.');
    }

    public function show(Reservation $reservation)
    {
        $reservation->load('client', 'user', 'items.product', 'sale', 'payments.user');

        return view('apartados.show', compact('reservation'));
    }

    public function ticket(Reservation $reservation)
    {
        $reservation->load('client', 'user', 'items.product', 'payments');

        return view('apartados.ticket', compact('reservation'));
    }

    public function pay(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'active') {
            return back()->with('error', 'Solo se pueden abonar apartados activos.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        try {
            DB::transaction(function () use ($data, $request, $reservation): void {
                $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
                if ($locked->status !== 'active' || (float) $data['amount'] > $locked->balance + 0.00001) {
                    throw new \RuntimeException('El abono no puede superar el saldo pendiente.');
                }
                ReservationPayment::create($data + [
                    'reservation_id' => $locked->id, 'user_id' => $request->user()->id,
                    'type' => 'payment', 'paid_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $reservation = $reservation->fresh();
        if ($request->boolean('print_ticket') && $reservation->balance <= 0) {
            return redirect()->route('apartados.ticket', $reservation)->with('success', 'Apartado pagado por completo.');
        }

        return back()->with('success', $reservation->balance > 0 ? 'Abono registrado correctamente.' : 'Apartado pagado por completo.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'active') {
            return back()->with('error', 'El apartado ya no está activo.');
        }
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:'.$reservation->paid_amount],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
        ]);
        DB::transaction(function () use ($reservation, $data, $request): void {
            $refund = (float) ($data['refund_amount'] ?? 0);
            if ($refund > 0) {
                ReservationPayment::create([
                    'reservation_id' => $reservation->id, 'user_id' => $request->user()->id,
                    'type' => 'refund', 'amount' => $refund, 'payment_method' => $data['payment_method'],
                    'notes' => 'Devolución por cancelación: '.$data['reason'], 'paid_at' => now(),
                ]);
            }
            $reservation->update(['status' => 'cancelled', 'notes' => trim(($reservation->notes ? $reservation->notes."\n" : '').'Cancelado: '.$data['reason'])]);
        });

        return back()->with('success', 'Apartado cancelado; los artículos volvieron a estar disponibles.');
    }
}

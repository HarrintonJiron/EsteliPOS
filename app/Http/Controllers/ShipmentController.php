<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Sale;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with('sale', 'client')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('department')) {
            $query->where('department', $request->string('department'));
        }

        return view('envios.index', ['shipments' => $query->paginate(20)->withQueryString(), 'departments' => Shipment::DEPARTMENTS]);
    }

    public function create(Request $request)
    {
        $salesQuery = Sale::query()->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId));
        $sale = $request->filled('sale_id') ? (clone $salesQuery)->with('client')->findOrFail($request->integer('sale_id')) : null;
        $shipment = $sale ? new Shipment([
            'sale_id' => $sale->id,
            'client_id' => $sale->client_id,
            'recipient_name' => $sale->billing_name ?: $sale->client?->name,
            'recipient_phone' => collect([$sale->billing_phone, $sale->client?->phone])
                ->first(fn ($value) => filled($value) && ! in_array(mb_strtolower(trim($value)), ['n/a', 'na', '-', '--', 'sin telefono', 'sin teléfono'], true)),
            'department' => $sale->client?->department,
            'municipality' => $sale->client?->municipality,
            'address' => $sale->billing_address ?: $sale->client?->address,
        ]) : null;

        return view('envios.create', [
            'shipment' => $shipment,
            'departments' => Shipment::DEPARTMENTS,
            'clients' => Client::orderBy('name')->get(),
            'sales' => $this->salesForPicker($request, $sale?->id),
            'shippedSales' => $this->shippedSales(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $shipment = Shipment::create($data + ['number' => NumberSequence::getNext('envio'), 'user_id' => $request->user()->id]);

        return redirect()->route('envios.ticket', $shipment)->with('success', 'Envío registrado correctamente.');
    }

    public function show(Shipment $shipment)
    {
        return view('envios.show', compact('shipment'));
    }

    public function label(Shipment $shipment)
    {
        return view('envios.label', compact('shipment'));
    }

    public function ticket(Shipment $shipment)
    {
        return view('envios.ticket', compact('shipment'));
    }

    public function edit(Request $request, Shipment $shipment)
    {
        return view('envios.edit', [
            'shipment' => $shipment,
            'departments' => Shipment::DEPARTMENTS,
            'clients' => Client::orderBy('name')->get(),
            'sales' => $this->salesForPicker($request, $shipment->sale_id),
            'shippedSales' => $this->shippedSales($shipment),
        ]);
    }

    /**
     * Ventas ofrecidas al relacionar un envío: las 100 más recientes no anuladas
     * (de la sucursal del usuario), con cliente y productos para poder reconocerlas,
     * más la venta ya elegida aunque sea más antigua.
     *
     * @return Collection<int, Sale>
     */
    private function salesForPicker(Request $request, ?int $selectedSaleId = null): Collection
    {
        $branchId = $request->user()->branch_id;

        $sales = Sale::query()
            ->whereNotIn('status', ['canceled', 'cancelled'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['client', 'details.product'])
            ->latest('date')
            ->latest('id')
            ->limit(100)
            ->get();

        if ($selectedSaleId && ! $sales->contains('id', $selectedSaleId)) {
            $selected = Sale::query()->with(['client', 'details.product'])->find($selectedSaleId);
            if ($selected) {
                $sales->prepend($selected);
            }
        }

        return $sales;
    }

    /**
     * Número del envío activo de cada venta, para avisar que ya tiene uno.
     *
     * @return array<int, string>
     */
    private function shippedSales(?Shipment $except = null): array
    {
        return Shipment::query()
            ->whereNotNull('sale_id')
            ->where('status', '!=', 'cancelled')
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->pluck('number', 'sale_id')
            ->all();
    }

    public function update(Request $request, Shipment $shipment)
    {
        $data = $this->validated($request);
        if ($data['status'] === 'shipped' && ! $shipment->shipped_at) {
            $data['shipped_at'] = now();
        }
        if ($data['status'] === 'delivered' && ! $shipment->delivered_at) {
            $data['delivered_at'] = now();
        }
        $shipment->update($data);

        return redirect()->route('envios.show', $shipment)->with('success', 'Envío actualizado.');
    }

    /**
     * Cambio rápido de estado desde el listado o la ficha del envío.
     */
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $status = $request->validate(['status' => ['required', Rule::in(Shipment::STATUSES)]])['status'];

        $data = ['status' => $status];
        if ($status === 'shipped' && ! $shipment->shipped_at) {
            $data['shipped_at'] = now();
        }
        if ($status === 'delivered' && ! $shipment->delivered_at) {
            $data['delivered_at'] = now();
        }
        $shipment->update($data);

        return back()->with('success', $shipment->number.': estado actualizado a '.$shipment->statusLabel().'.');
    }

    private function validated(Request $request): array
    {
        if (! $request->filled('status')) {
            $request->merge(['status' => 'pending']);
        }

        $data = $request->validate([
            'sale_id' => 'nullable|exists:sales,id', 'client_id' => 'nullable|exists:clients,id',
            'recipient_name' => 'required|string|max:150', 'recipient_phone' => 'nullable|string|max:30',
            'department' => ['required', Rule::in(Shipment::DEPARTMENTS)], 'municipality' => 'nullable|string|max:80',
            'address' => 'required|string|max:1000', 'is_fragile' => 'nullable|boolean',
            'reference' => 'nullable|string|max:1000', 'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100', 'shipping_cost' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(Shipment::STATUSES)], 'notes' => 'nullable|string|max:2000',
        ]);
        $data['is_fragile'] = $request->boolean('is_fragile');

        return $data;
    }
}

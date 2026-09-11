<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\Sale;
use App\Models\Shipment;
use Illuminate\Http\Request;
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
            'recipient_phone' => $sale->billing_phone ?: $sale->client?->phone,
            'department' => $sale->client?->department,
            'municipality' => $sale->client?->municipality,
            'address' => $sale->billing_address ?: $sale->client?->address,
        ]) : null;

        return view('envios.create', [
            'shipment' => $shipment,
            'departments' => Shipment::DEPARTMENTS,
            'clients' => Client::orderBy('name')->get(),
            'sales' => $salesQuery->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $shipment = Shipment::create($data + ['number' => NumberSequence::getNext('envio'), 'user_id' => $request->user()->id]);

        return redirect()->route('envios.show', $shipment)->with('success', 'Envío registrado correctamente.');
    }

    public function show(Shipment $shipment)
    {
        return view('envios.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        return view('envios.edit', ['shipment' => $shipment, 'departments' => Shipment::DEPARTMENTS, 'clients' => Client::orderBy('name')->get(), 'sales' => Sale::latest()->limit(100)->get()]);
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'sale_id' => 'nullable|exists:sales,id', 'client_id' => 'nullable|exists:clients,id',
            'recipient_name' => 'required|string|max:150', 'recipient_phone' => 'nullable|string|max:30',
            'department' => ['required', Rule::in(Shipment::DEPARTMENTS)], 'municipality' => 'nullable|string|max:80',
            'address' => 'required|string|max:1000', 'reference' => 'nullable|string|max:1000', 'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100', 'shipping_cost' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(Shipment::STATUSES)], 'notes' => 'nullable|string|max:2000',
        ]);
    }
}

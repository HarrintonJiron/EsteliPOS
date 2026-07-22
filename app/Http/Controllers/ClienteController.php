<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\CreditService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private CreditService $credit) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Client::query()->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%");
            });
        }

        $clients = $query->paginate($perPage)->through(function (Client $client) {
            $summary = $this->credit->clientCreditSummary($client);

            return (object) array_merge($client->toArray(), $summary);
        });

        $stats = [
            'total' => Client::count(),
            'with_credit' => Client::where('credit_enabled', true)->count(),
            'over_limit' => Client::where('credit_enabled', true)->get()->filter(fn (Client $c) => $c->isOverCreditLimit())->count(),
            'portfolio' => $this->credit->portfolioSummary(),
        ];

        return view('clientes.index', compact('clients', 'stats'));
    }

    public function show($id)
    {
        $client = Client::with(['sales' => fn ($q) => $q->latest()->limit(10)])->findOrFail($id);
        $creditSummary = $this->credit->clientCreditSummary($client);

        return view('clientes.show', compact('client', 'creditSummary'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());

        return redirect()->route('clientes.show', $client->id)
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $creditSummary = $this->credit->clientCreditSummary($client);

        return view('clientes.edit', compact('client', 'creditSummary'));
    }

    public function update(ClientRequest $request, $id)
    {
        $client = Client::findOrFail($id);
        $client->update($request->validated());

        return redirect()->route('clientes.show', $client->id)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado.');
    }

    /**
     * Toggle credit enabled for a client and optionally set limit/days.
     */
    public function toggleCredit(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'credit_enabled' => 'required|boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:1|max:365',
        ]);

        $client->update([
            'credit_enabled' => $data['credit_enabled'],
            'credit_limit' => $data['credit_limit'] ?? $client->credit_limit,
            'credit_days' => $data['credit_days'] ?? $client->credit_days,
        ]);

        return redirect()->route('clientes.show', $client->id)->with('success', 'Configuración de crédito actualizada.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Requests\ClientRequest;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $clients = Client::latest()->paginate($perPage);

        return view('clientes.index', compact('clients'));
    }

    public function show($id)
    {
        $client = Client::with('sales')->findOrFail($id);

        return view('clientes.show', compact('client'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());

        return redirect()->route('clientes.show', $client->id);
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('clientes.edit', compact('client'));
    }

    public function update(ClientRequest $request, $id)
    {
        $client = Client::findOrFail($id);

        $client->update($request->validated());

        return redirect()->route('clientes.show', $client->id);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return redirect()->route('clientes.index');
    }
}

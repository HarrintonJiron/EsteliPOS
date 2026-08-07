<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRateService) {}

    public function index()
    {
        $rates = ExchangeRate::where('is_active', true)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->orderBy('from_currency')
            ->orderBy('to_currency')
            ->get();

        $rates = $rates->groupBy(['from_currency', 'to_currency'])
            ->map(function ($group) {
                return $group->first();
            })
            ->values();

        return view('settings.exchange-rates.index', compact('rates'));
    }

    public function create()
    {
        return view('settings.exchange-rates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_currency' => 'required|string|size:3|in:NIO,USD,EUR',
            'to_currency' => 'required|string|size:3|in:NIO,USD,EUR|different:from_currency',
            'rate' => 'required|numeric|min:0.000001|max:999999.999999',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'is_active' => 'nullable|boolean',
        ]);

        $this->exchangeRateService->createRate($data);

        return redirect()->route('settings.exchange-rates.index')
            ->with('success', 'Tipo de cambio creado correctamente.');
    }

    public function edit(ExchangeRate $exchangeRate)
    {
        return view('settings.exchange-rates.edit', compact('exchangeRate'));
    }

    public function update(Request $request, ExchangeRate $exchangeRate)
    {
        $data = $request->validate([
            'from_currency' => 'required|string|size:3|in:NIO,USD,EUR',
            'to_currency' => 'required|string|size:3|in:NIO,USD,EUR|different:from_currency',
            'rate' => 'required|numeric|min:0.000001|max:999999.999999',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'is_active' => 'nullable|boolean',
        ]);

        $this->exchangeRateService->updateRate($exchangeRate, $data);

        return redirect()->route('settings.exchange-rates.index')
            ->with('success', 'Tipo de cambio actualizado correctamente.');
    }

    public function destroy(ExchangeRate $exchangeRate)
    {
        $this->exchangeRateService->deleteRate($exchangeRate);

        return redirect()->route('settings.exchange-rates.index')
            ->with('success', 'Tipo de cambio eliminado.');
    }
}

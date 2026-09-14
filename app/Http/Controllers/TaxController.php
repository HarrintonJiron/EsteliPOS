<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxRequest;
use App\Models\Setting;
use App\Models\Tax;
use App\Services\InvoiceTaxDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $query = Tax::query()->orderBy('rate', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $taxes = $query->get();
        $taxDisplayMode = app(InvoiceTaxDisplayService::class)->mode();
        $taxDisplayModes = InvoiceTaxDisplayService::options();

        return view('contabilidad.impuestos.index', compact('taxes', 'taxDisplayMode', 'taxDisplayModes'));
    }

    public function updateDisplayMode(Request $request)
    {
        $validated = $request->validate([
            'invoice_tax_display_mode' => 'required|in:general,exempt,hide',
        ]);

        Setting::set(
            InvoiceTaxDisplayService::SETTING_KEY,
            $validated['invoice_tax_display_mode'],
            'string',
            'general',
            'Controla como se presenta el impuesto en documentos impresos.',
        );

        return redirect()
            ->route('settings.taxes.index')
            ->with('success', 'Modo de visualizacion de impuestos actualizado correctamente.');
    }

    public function create()
    {
        return view('contabilidad.impuestos.create');
    }

    public function store(TaxRequest $request)
    {
        $data = $request->validated();
        $data['rate'] = $data['rate'] / 100;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $data['is_active'] && $request->boolean('is_default');

        DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                Tax::where('is_default', true)->update(['is_default' => false]);
            }

            Tax::create($data);
        });

        return redirect()->route('settings.taxes.index')->with('success', 'Impuesto creado correctamente.');
    }

    public function edit(Tax $tax)
    {
        return view('contabilidad.impuestos.edit', compact('tax'));
    }

    public function update(TaxRequest $request, Tax $tax)
    {
        $data = $request->validated();
        $data['rate'] = $data['rate'] / 100;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $data['is_active'] && $request->boolean('is_default');

        DB::transaction(function () use ($data, $tax) {
            if ($data['is_default'] && !$tax->is_default) {
                Tax::where('is_default', true)->update(['is_default' => false]);
            }

            $tax->update($data);
        });

        return redirect()->route('settings.taxes.index')->with('success', 'Impuesto actualizado correctamente.');
    }

    public function destroy(Tax $tax)
    {
        if ($tax->is_default) {
            return back()->with('error', 'No se puede eliminar el impuesto predeterminado. Asigne otro como predeterminado primero.');
        }

        if ($tax->products()->exists()) {
            return back()->with('error', 'No se puede eliminar un impuesto asignado a productos.');
        }

        $tax->delete();

        return redirect()->route('settings.taxes.index')->with('success', 'Impuesto eliminado correctamente.');
    }
}

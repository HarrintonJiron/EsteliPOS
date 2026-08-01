<?php

namespace App\Http\Controllers;

use App\Http\Requests\CostCenterRequest;
use App\Models\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = CostCenter::orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $costCenters = $query->get();

        return view('contabilidad.centros-costo.index', [
            'costCenters' => $costCenters,
            'types' => CostCenter::TYPES,
        ]);
    }

    public function create()
    {
        return view('contabilidad.centros-costo.create', [
            'types' => CostCenter::TYPES,
        ]);
    }

    public function store(CostCenterRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        CostCenter::create($data);

        return redirect()->route('contabilidad.centros-costo.index')->with('success', 'Centro de costo creado correctamente.');
    }

    public function edit(CostCenter $centro_costo)
    {
        return view('contabilidad.centros-costo.edit', [
            'costCenter' => $centro_costo,
            'types' => CostCenter::TYPES,
        ]);
    }

    public function update(CostCenterRequest $request, CostCenter $centro_costo)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $centro_costo->update($data);

        return redirect()->route('contabilidad.centros-costo.index')->with('success', 'Centro de costo actualizado correctamente.');
    }

    public function destroy(CostCenter $centro_costo)
    {
        if ($centro_costo->journalEntryLines()->exists()) {
            return back()->with('error', 'No se puede eliminar un centro de costo con movimientos asociados.');
        }

        $centro_costo->delete();

        return redirect()->route('contabilidad.centros-costo.index')->with('success', 'Centro de costo eliminado correctamente.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Http\Requests\SupplierRequest;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Supplier::withCount('purchases');

        // Búsqueda por nombre, código, RUC o contacto
        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('business_name', 'like', "%{$q}%");
            });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por condición de pago
        if ($request->filled('payment_condition')) {
            $query->where('payment_condition', $request->payment_condition);
        }

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . $request->type . '%');
        }

        // Filtro por ciudad
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Ordenamiento
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', 'asc');
        $allowedSorts = ['name', 'code', 'ruc', 'created_at', 'purchases_count'];

        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'purchases_count') {
                $query->orderBy('purchases_count', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        }

        $suppliers = $query->paginate($perPage)->withQueryString();

        // Estadísticas
        $stats = [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('status', 'active')->count(),
            'inactive_suppliers' => Supplier::where('status', 'inactive')->count(),
            'suppliers_with_credit' => Supplier::whereNotNull('credit_limit')->where('credit_limit', '>', 0)->count(),
        ];

        return view('proveedores.index', compact('suppliers', 'stats'));
    }

    public function show($id)
    {
        $supplier = Supplier::with(['purchases' => function ($q) {
            $q->with('details.product')->latest();
        }])->findOrFail($id);

        // Estadísticas del proveedor
        $supplierStats = [
            'total_purchases' => $supplier->getTotalPurchases(),
            'pending_purchases' => $supplier->getPendingPurchases(),
            'credit_used' => $supplier->getCreditUsed(),
            'credit_available' => $supplier->getCreditAvailable(),
            'total_orders' => $supplier->purchases->count(),
            'completed_orders' => $supplier->purchases->where('status', 'completed')->count(),
            'pending_orders' => $supplier->purchases->where('status', 'pending')->count(),
            'average_purchase' => $supplier->purchases->where('status', 'completed')->avg('total') ?? 0,
            'last_purchase' => $supplier->purchases->first(),
        ];

        return view('proveedores.show', compact('supplier', 'supplierStats'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return redirect()->route('proveedores.show', $supplier->id)
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('proveedores.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update($request->validated());

        return redirect()->route('proveedores.show', $supplier->id)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchases()->exists()) {
            return redirect()->route('proveedores.index')
                ->with('error', 'No se puede eliminar el proveedor porque tiene compras asociadas.');
        }

        $supplier->delete();

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }

    public function getCreditInfo($id)
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json([
            'id' => $supplier->id,
            'name' => $supplier->name,
            'credit_limit' => $supplier->credit_limit ?? 0,
            'credit_used' => $supplier->getCreditUsed(),
            'credit_available' => $supplier->getCreditAvailable(),
            'has_credit_available' => $supplier->hasCreditAvailable(),
            'payment_condition' => $supplier->payment_condition,
            'payment_condition_label' => $supplier->payment_condition_label,
        ]);
    }

    public function export(Request $request)
    {
        $suppliers = Supplier::withCount('purchases')
            ->orderBy('name')
            ->get();

        $csv = "Codigo,Nombre,Razon Social,RUC,Contacto,Telefono,Email,Ciudad,Condicion,Limite Credito,Estado,Compras\n";

        foreach ($suppliers as $supplier) {
            $csv .= sprintf(
                "%s,\"%s\",\"%s\",%s,\"%s\",%s,%s,\"%s\",\"%s\",%.2f,%s,%d\n",
                $supplier->code ?? '',
                str_replace('"', '""', $supplier->name),
                str_replace('"', '""', $supplier->business_name ?? ''),
                $supplier->ruc ?? '',
                str_replace('"', '""', $supplier->contact_name ?? ''),
                $supplier->phone ?? '',
                $supplier->email ?? '',
                str_replace('"', '""', $supplier->city ?? ''),
                $supplier->payment_condition_label ?? '—',
                $supplier->credit_limit ?? 0,
                $supplier->status_label,
                $supplier->purchases_count
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="proveedores_' . now()->format('Ymd_His') . '.csv"',
        ]);
    }
}

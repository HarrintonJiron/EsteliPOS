<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\InventoryMovement;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Product::with('category');

        // Búsqueda por nombre o código
        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('lot', 'like', "%{$q}%")
                    ->orWhere('active_ingredient', 'like', "%{$q}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtro por estado de inventario
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->whereRaw('stock <= low_stock_threshold');
                    break;
                case 'expired':
                    $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('expiry_date')
                          ->whereDate('expiry_date', '>=', now())
                          ->whereDate('expiry_date', '<=', now()->addDays(30));
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
            }
        }

        // Filtro por estado del producto
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        // Filtro por ubicación
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Ordenamiento
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', 'asc');
        $allowedSorts = ['name', 'code', 'stock', 'sale_price', 'expiry_date', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($perPage)->withQueryString();

        // Estadísticas para el dashboard
        $stats = [
            'total_products' => Product::where('status', 'active')->count(),
            'low_stock_count' => Product::where('status', 'active')
                ->whereRaw('stock <= low_stock_threshold')
                ->count(),
            'expired_count' => Product::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now())
                ->count(),
            'expiring_soon_count' => Product::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(30))
                ->count(),
            'total_inventory_value' => Product::where('status', 'active')
                ->selectRaw('SUM(stock * purchase_price) as total_value')
                ->first()
                ->total_value ?? 0,
        ];

        $categories = Category::orderBy('name')->get();

        return view('inventario.index', compact('products', 'stats', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('inventario.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products',
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'lot' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'registration_number' => 'nullable|string|max:100',
            'active_ingredient' => 'nullable|string|max:255',
            'concentration' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,discontinued',
            'observations' => 'nullable|string|max:2000',
        ]);

        $product = Product::create($validated);

        if ($validated['stock'] > 0) {
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $validated['stock'],
                'stock_after' => $validated['stock'],
                'reference' => 'initial_stock',
                'note' => 'Stock inicial al crear producto',
                'user_id' => $request->user()?->id ?? 1,
            ]);
        }

        return redirect()->route('inventario.index')->with('success', 'Producto creado correctamente.');
    }

    public function show($id)
    {
        $product = Product::with(['category', 'inventoryMovements.user'])->findOrFail($id);

        $movements = $product->inventoryMovements()
            ->with('user')
            ->latest()
            ->paginate(10);

        $productStats = [
            'total_movements' => $product->inventoryMovements()->count(),
            'total_in' => $product->inventoryMovements()->where('type', 'in')->sum('quantity'),
            'total_out' => $product->inventoryMovements()->where('type', 'out')->sum('quantity'),
            'last_movement' => $product->inventoryMovements()->latest()->first(),
        ];

        return view('inventario.show', compact('product', 'movements', 'productStats'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        return view('inventario.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products,code,' . $product->id,
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'lot' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'registration_number' => 'nullable|string|max:100',
            'active_ingredient' => 'nullable|string|max:255',
            'concentration' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,discontinued',
            'observations' => 'nullable|string|max:2000',
        ]);

        $product->update($validated);

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->purchaseDetails()->exists() || $product->saleDetails()->exists()) {
            return redirect()->route('inventario.index')
                ->with('error', 'No se puede eliminar el producto porque tiene movimientos asociados.');
        }

        $product->delete();

        return redirect()->route('inventario.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function dashboard()
    {
        $stats = [
            'total_products' => Product::where('status', 'active')->count(),
            'low_stock' => Product::where('status', 'active')
                ->whereRaw('stock <= low_stock_threshold')
                ->get(),
            'expired' => Product::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now())
                ->get(),
            'expiring_soon' => Product::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(30))
                ->get(),
            'top_products' => Product::where('status', 'active')
                ->orderByDesc('stock')
                ->limit(10)
                ->get(),
            'inventory_value_by_category' => Category::withSum('products as value', DB::raw('stock * purchase_price'))
                ->get(),
        ];

        return view('inventario.dashboard', compact('stats'));
    }

    public function export(Request $request)
    {
        $products = Product::with('category')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $csv = "Codigo,Nombre,Categoria,Lote,Vencimiento,Stock,Ubicacion,Precio Compra,Precio Venta,Estado\n";

        foreach ($products as $product) {
            $csv .= sprintf(
                "%s,\"%s\",\"%s\",%s,%s,%d,\"%s\",%.2f,%.2f,%s\n",
                $product->code,
                str_replace('"', '""', $product->name),
                str_replace('"', '""', $product->category?->name ?? ''),
                $product->lot ?? '',
                $product->expiry_date?->format('d/m/Y') ?? '',
                $product->stock,
                $product->location ?? '',
                $product->purchase_price,
                $product->sale_price,
                $product->status_label
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventario_' . now()->format('Ymd_His') . '.csv"',
        ]);
    }
}

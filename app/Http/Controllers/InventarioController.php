<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 15);
        $viewMode = $request->query('view', 'list');
        $periodDays = (int) $request->query('period', 30);
        $salesSub = $this->inventory->salesStatsSubquery($periodDays);

        $query = Product::query()->with('category');

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('lot', 'like', "%{$q}%")
                    ->orWhere('active_ingredient', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'low' => $query->whereRaw('stock <= COALESCE(low_stock_threshold, 10)'),
                'expired' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now()),
                'expiring_soon' => $query->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', now())
                    ->whereDate('expiry_date', '<=', now()->addDays(30)),
                'out_of_stock' => $query->where('stock', '<=', 0),
                'discrepancy' => $query->whereIn('id', $this->discrepantProductIds()),
                default => null,
            };
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->location.'%');
        }

        $query->leftJoinSub($salesSub, 'sales_stats', 'sales_stats.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(sales_stats.sold_qty, 0) as sold_qty')
            ->selectRaw('COALESCE(sales_stats.sold_revenue, 0) as sold_revenue')
            ->selectRaw('COALESCE(sales_stats.sale_count, 0) as sale_count')
            ->selectRaw('CASE WHEN products.stock > 0 THEN ROUND(COALESCE(sales_stats.sold_qty, 0) / products.stock, 2) ELSE 0 END as rotation_index');

        match ($viewMode) {
            'top_sellers' => $query->orderByDesc('sold_qty')->orderBy('name'),
            'low_rotation' => $query->where('stock', '>', 0)->orderBy('rotation_index')->orderByDesc('stock'),
            'dead_stock' => $query->where('stock', '>', 0)->whereRaw('COALESCE(sales_stats.sold_qty, 0) = 0')->orderByDesc('stock'),
            'high_rotation' => $query->where('stock', '>', 0)->orderByDesc('rotation_index'),
            default => $this->applySorting($query, $request),
        };

        $products = $query->paginate($perPage)->withQueryString();

        $stats = $this->buildStats();
        $movementStats = $this->inventory->movementStats($periodDays);
        $categories = Category::orderBy('name')->get();
        $discrepancyCount = count($this->discrepantProductIds());

        return view('inventario.index', compact(
            'products', 'stats', 'categories', 'viewMode', 'periodDays',
            'movementStats', 'discrepancyCount'
        ));
    }

    public function dashboard(): View
    {
        $periodDays = 30;
        $salesSub = $this->inventory->salesStatsSubquery($periodDays);

        $topSellers = Product::query()
            ->with('category')
            ->leftJoinSub($salesSub, 'sales_stats', 'sales_stats.product_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->select('products.*')
            ->selectRaw('COALESCE(sales_stats.sold_qty, 0) as sold_qty')
            ->selectRaw('COALESCE(sales_stats.sold_revenue, 0) as sold_revenue')
            ->orderByDesc('sold_qty')
            ->limit(10)
            ->get();

        $lowRotation = Product::query()
            ->with('category')
            ->leftJoinSub($salesSub, 'sales_stats', 'sales_stats.product_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->where('products.stock', '>', 0)
            ->select('products.*')
            ->selectRaw('COALESCE(sales_stats.sold_qty, 0) as sold_qty')
            ->selectRaw('CASE WHEN products.stock > 0 THEN ROUND(COALESCE(sales_stats.sold_qty, 0) / products.stock, 2) ELSE 0 END as rotation_index')
            ->orderBy('rotation_index')
            ->orderByDesc('stock')
            ->limit(10)
            ->get();

        $deadStock = Product::query()
            ->with('category')
            ->leftJoinSub($salesSub, 'sales_stats', 'sales_stats.product_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->where('products.stock', '>', 0)
            ->whereRaw('COALESCE(sales_stats.sold_qty, 0) = 0')
            ->select('products.*')
            ->orderByDesc('stock')
            ->limit(10)
            ->get();

        $valueByCategory = Category::query()
            ->with(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get()
            ->map(fn ($cat) => (object) [
                'name' => $cat->name,
                'product_count' => $cat->products->count(),
                'inventory_value' => $cat->products->sum(fn ($p) => $p->stock * $p->purchase_price),
            ])
            ->sortByDesc('inventory_value')
            ->values();

        $stats = $this->buildStats();
        $movementStats = $this->inventory->movementStats($periodDays);
        $discrepancies = $this->inventory->reconcileAll(false)['discrepancies'];

        return view('inventario.dashboard', compact(
            'topSellers', 'lowRotation', 'deadStock', 'valueByCategory',
            'stats', 'movementStats', 'discrepancies', 'periodDays'
        ));
    }

    public function bulk(): View
    {
        $categories = Category::orderBy('name')->get();
        $suggestedCode = $this->inventory->nextProductCode();

        return view('inventario.bulk', compact('categories', 'suggestedCode'));
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_category_id' => 'required|exists:categories,id',
            'default_unit' => 'required|string|max:50',
            'default_low_stock' => 'nullable|integer|min:1',
            'products' => 'required|json',
        ]);

        $rows = json_decode($validated['products'], true);
        if (! is_array($rows) || empty($rows)) {
            return back()->withErrors(['products' => 'Agrega al menos un producto.']);
        }

        $created = 0;
        $errors = [];
        $userId = $request->user()?->id;

        DB::transaction(function () use ($rows, $validated, &$created, &$errors, $userId) {
            foreach ($rows as $index => $row) {
                $line = $index + 1;
                $name = trim($row['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $code = trim($row['code'] ?? '') ?: $this->inventory->nextProductCode();
                if (Product::where('code', $code)->exists()) {
                    $errors[] = "Línea {$line}: el código «{$code}» ya existe.";

                    continue;
                }

                $stock = max(0, (int) ($row['stock'] ?? 0));
                $product = Product::create([
                    'category_id' => (int) ($row['category_id'] ?? $validated['default_category_id']),
                    'name' => $name,
                    'code' => $code,
                    'purchase_price' => (float) ($row['purchase_price'] ?? 0),
                    'sale_price' => (float) ($row['sale_price'] ?? 0),
                    'stock' => 0,
                    'unit' => $row['unit'] ?? $validated['default_unit'],
                    'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? $validated['default_low_stock'] ?? 10),
                    'location' => $row['location'] ?? null,
                    'status' => 'active',
                ]);

                if ($stock > 0) {
                    $this->inventory->stockIn(
                        $product,
                        $stock,
                        'bulk_import',
                        'Stock inicial — carga masiva',
                        $userId
                    );
                }

                $created++;
            }
        });

        if (! empty($errors)) {
            return redirect()->route('inventario.bulk')
                ->with('error', implode(' ', $errors))
                ->with('success', "Se crearon {$created} productos.");
        }

        return redirect()->route('inventario.index')
            ->with('success', "Carga masiva completada: {$created} productos registrados.");
    }

    public function reconcile(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $result = $this->inventory->reconcileAll(true);

        return redirect()->route('inventario.dashboard')
            ->with('success', "Reconciliación completada. {$result['fixed']} productos corregidos.");
    }

    public function nextCode(Request $request): JsonResponse
    {
        $prefix = $request->query('prefix', 'PROD');

        return response()->json([
            'code' => $this->inventory->nextProductCode($prefix),
        ]);
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('inventario.create', compact('categories'));
    }

    public function quick(): View
    {
        $categories = Category::orderBy('name')->get();
        $defaultCategory = $categories->first();

        return view('inventario.quick', compact('categories', 'defaultCategory'));
    }

    public function lookupCode(string $code): JsonResponse
    {
        $product = Product::with('category')->where('code', $code)->first();

        if (! $product) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'sale_price' => $product->sale_price,
                'stock' => $product->stock,
                'category' => $product->category?->name,
                'url' => route('inventario.show', $product->id),
            ],
        ]);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products,code',
            'name' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'nullable|string|max:50',
            'low_stock_threshold' => 'nullable|integer|min:1',
        ]);

        $categoryId = $validated['category_id'] ?? Category::query()->value('id');
        if (! $categoryId) {
            return back()->withErrors(['category_id' => 'Crea al menos una categoría antes de registrar productos.']);
        }

        $purchasePrice = $validated['purchase_price'] ?? round($validated['sale_price'] * 0.85, 2);
        $stock = (int) ($validated['stock'] ?? 0);

        $product = Product::create([
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'purchase_price' => $purchasePrice,
            'sale_price' => $validated['sale_price'],
            'stock' => 0,
            'unit' => $validated['unit'] ?? 'unidad',
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'status' => 'active',
        ]);

        if ($stock > 0) {
            $this->inventory->stockIn(
                $product,
                $stock,
                'quick_entry',
                'Stock inicial — registro rápido',
                $request->user()?->id
            );
        }

        if ($request->boolean('add_another')) {
            return redirect()->route('inventario.quick')
                ->with('success', "«{$product->name}» guardado. Escanea el siguiente.");
        }

        return redirect()->route('inventario.index')
            ->with('success', "Producto «{$product->name}» registrado correctamente.");
    }

    public function store(Request $request): RedirectResponse
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

        $stock = (int) $validated['stock'];
        $validated['stock'] = 0;

        $product = Product::create($validated);

        if ($stock > 0) {
            $this->inventory->stockIn(
                $product,
                $stock,
                'initial_stock',
                'Stock inicial al crear producto',
                $request->user()?->id
            );
        }

        return redirect()->route('inventario.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(int $id): View
    {
        $product = Product::with(['category', 'inventoryMovements.user'])->findOrFail($id);

        $movements = $product->inventoryMovements()
            ->with('user')
            ->latest()
            ->paginate(15);

        $periodDays = 90;
        $salesData = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sale_details.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.date', '>=', now()->subDays($periodDays))
            ->selectRaw('COALESCE(SUM(sale_details.quantity), 0) as sold_qty')
            ->selectRaw('COALESCE(SUM(sale_details.subtotal), 0) as sold_revenue')
            ->selectRaw('COUNT(DISTINCT sale_details.sale_id) as sale_count')
            ->first();

        $soldQty = (int) ($salesData->sold_qty ?? 0);

        $productStats = [
            'total_movements' => $product->inventoryMovements()->count(),
            'total_in' => (int) $product->inventoryMovements()->where('type', 'in')->sum('quantity'),
            'total_out' => (int) $product->inventoryMovements()->where('type', 'out')->sum('quantity'),
            'calculated_stock' => $product->calculatedStock(),
            'has_discrepancy' => $product->hasStockDiscrepancy(),
            'sold_qty' => $soldQty,
            'sold_revenue' => (float) ($salesData->sold_revenue ?? 0),
            'sale_count' => (int) ($salesData->sale_count ?? 0),
            'rotation_index' => $product->rotationIndex($soldQty),
            'last_movement' => $product->inventoryMovements()->latest()->first(),
        ];

        return view('inventario.show', compact('product', 'movements', 'productStats', 'periodDays'));
    }

    public function edit(int $id): View
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return view('inventario.edit', compact('product', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products,code,'.$product->id,
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

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        if ($product->purchaseDetails()->exists() || $product->saleDetails()->exists()) {
            return redirect()->route('inventario.index')
                ->with('error', 'No se puede eliminar el producto porque tiene movimientos asociados.');
        }

        $product->delete();

        return redirect()->route('inventario.index')->with('success', 'Producto eliminado correctamente.');
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
            'Content-Disposition' => 'attachment; filename="inventario_'.now()->format('Ymd_His').'.csv"',
        ]);
    }

    /** @return list<int> */
    private function discrepantProductIds(): array
    {
        return DB::table('products')
            ->leftJoinSub(
                DB::table('inventory_movements')
                    ->select('product_id')
                    ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as calculated")
                    ->groupBy('product_id'),
                'movements_sum',
                'movements_sum.product_id',
                '=',
                'products.id'
            )
            ->whereRaw('products.stock != COALESCE(movements_sum.calculated, 0)')
            ->pluck('products.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function buildStats(): array
    {
        return [
            'total_products' => Product::where('status', 'active')->count(),
            'low_stock_count' => Product::where('status', 'active')
                ->whereRaw('stock <= COALESCE(low_stock_threshold, 10)')
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
            'out_of_stock_count' => Product::where('status', 'active')->where('stock', '<=', 0)->count(),
            'total_inventory_value' => Product::where('status', 'active')
                ->selectRaw('SUM(stock * purchase_price) as total_value')
                ->value('total_value') ?? 0,
            'total_sale_value' => Product::where('status', 'active')
                ->selectRaw('SUM(stock * sale_price) as total_value')
                ->value('total_value') ?? 0,
        ];
    }

    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', 'asc');
        $allowed = ['name', 'code', 'stock', 'sale_price', 'expiry_date', 'created_at', 'sold_qty', 'rotation_index'];

        if (in_array($sortBy, $allowed, true)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name');
        }
    }
}

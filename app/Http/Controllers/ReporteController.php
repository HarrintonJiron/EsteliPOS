<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $reportType = $request->get('report_type', 'sales');

        $data = [];
        $summary = [];

        switch ($reportType) {
            case 'sales':
                $data = $this->getSalesReport($startDate, $endDate, $request);
                $summary = $this->getSalesSummary($startDate, $endDate);
                break;
            case 'purchases':
                $data = $this->getPurchasesReport($startDate, $endDate, $request);
                $summary = $this->getPurchasesSummary($startDate, $endDate);
                break;
            case 'inventory':
                $data = $this->getInventoryReport($request);
                $summary = $this->getInventorySummary();
                break;
            case 'kardex':
                $data = $this->getKardexReport($request);
                break;
            case 'profit':
                $data = $this->getProfitReport($startDate, $endDate);
                $summary = $this->getProfitSummary($startDate, $endDate);
                break;
            case 'abc':
                $rows = $this->buildAbcRows($startDate, $endDate);
                $data = $this->paginateCollection($rows);
                $summary = $this->summarizeAbc($rows);
                break;
            case 'aging':
                $rows = $this->buildAgingRows();
                $data = $this->paginateCollection($rows);
                $summary = $this->summarizeAging($rows);
                break;
            case 'slow':
                $data = $this->getSlowMoversReport($startDate, $endDate);
                $summary = $this->getSlowMoversSummary($startDate, $endDate);
                break;
            case 'top_clients':
                $rows = $this->buildTopClientRows($startDate, $endDate);
                $data = $this->paginateCollection($rows);
                $summary = $this->summarizeTopClients($rows);
                break;
            case 'sellers':
                $rows = $this->buildSellerRows($startDate, $endDate);
                $data = $this->paginateCollection($rows);
                $summary = $this->summarizeSellers($rows);
                break;
            case 'categories':
                $rows = $this->buildCategoryRows($startDate, $endDate);
                $data = $this->paginateCollection($rows);
                $summary = $this->summarizeCategories($rows);
                break;
            default:
                $reportType = 'sales';
                $data = $this->getSalesReport($startDate, $endDate, $request);
                $summary = $this->getSalesSummary($startDate, $endDate);
                break;
        }

        // Datos para filtros
        $products = Product::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('reportes.index', compact(
            'data', 'summary', 'reportType', 'startDate', 'endDate',
            'products', 'clients', 'suppliers'
        ));
    }

    private function getSalesReport($startDate, $endDate, $request)
    {
        $query = Sale::with(['client', 'details.product'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        return $query->latest()->paginate(35);
    }

    private function getSalesSummary($startDate, $endDate)
    {
        return [
            'total_sales' => Sale::whereBetween('date', [$startDate, $endDate])->sum('total'),
            'total_count' => Sale::whereBetween('date', [$startDate, $endDate])->count(),
            'avg_sale' => Sale::whereBetween('date', [$startDate, $endDate])->avg('total'),
            'by_status' => Sale::whereBetween('date', [$startDate, $endDate])
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
                ->groupBy('status')
                ->get(),
        ];
    }

    private function getPurchasesReport($startDate, $endDate, $request)
    {
        $query = Purchase::with(['supplier', 'details.product'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate(35);
    }

    private function getPurchasesSummary($startDate, $endDate)
    {
        return [
            'total_purchases' => Purchase::whereBetween('date', [$startDate, $endDate])->sum('total'),
            'total_count' => Purchase::whereBetween('date', [$startDate, $endDate])->count(),
            'avg_purchase' => Purchase::whereBetween('date', [$startDate, $endDate])->avg('total'),
        ];
    }

    private function getInventoryReport($request)
    {
        $query = Product::with('category');

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->whereColumn('stock', '<=', 'low_stock_threshold')->orWhere('stock', '<=', 10);
                    break;
                case 'out':
                    $query->where('stock', '<=', 0);
                    break;
                case 'expired':
                    $query->whereNotNull('expiry_date')->where('expiry_date', '<', Carbon::today());
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('expiry_date')
                        ->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays(30)]);
                    break;
            }
        }

        return $query->orderBy('name')->paginate(35);
    }

    private function getInventorySummary()
    {
        $today = Carbon::today();

        return [
            'total_products' => Product::count(),
            'total_value' => Product::select(DB::raw('SUM(stock * purchase_price) as total'))->value('total'),
            'low_stock' => Product::whereColumn('stock', '<=', 'low_stock_threshold')->orWhere('stock', '<=', 10)->count(),
            'expired' => Product::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count(),
            'expiring_soon' => Product::whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                ->count(),
        ];
    }

    private function getKardexReport($request)
    {
        $query = InventoryMovement::with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query->latest()->paginate(35);
    }

    private function getProfitReport($startDate, $endDate)
    {
        return Sale::with('details.product')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->latest()
            ->paginate(35);
    }

    private function getProfitSummary($startDate, $endDate)
    {
        $sales = Sale::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total');

        $costs = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->select(DB::raw('SUM(sale_details.quantity * products.purchase_price) as total_cost'))
            ->value('total_cost');

        return [
            'total_sales' => $sales,
            'total_cost' => $costs ?? 0,
            'gross_profit' => $sales - ($costs ?? 0),
            'profit_margin' => $sales > 0 ? (($sales - ($costs ?? 0)) / $sales) * 100 : 0,
        ];
    }

    public function exportExcel(Request $request)
    {
        $reportType = $request->get('report_type', 'sales');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $filename = "reporte_{$reportType}_".Carbon::now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($reportType, $startDate, $endDate, $request) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8

            switch ($reportType) {
                case 'sales':
                    $this->exportSalesCSV($output, $startDate, $endDate, $request);
                    break;
                case 'purchases':
                    $this->exportPurchasesCSV($output, $startDate, $endDate, $request);
                    break;
                case 'inventory':
                    $this->exportInventoryCSV($output, $request);
                    break;
                case 'kardex':
                    $this->exportKardexCSV($output, $request);
                    break;
                case 'profit':
                    $this->exportProfitCSV($output, $startDate, $endDate);
                    break;
                case 'abc':
                    $this->exportAbcCSV($output, $startDate, $endDate);
                    break;
                case 'aging':
                    $this->exportAgingCSV($output);
                    break;
                case 'slow':
                    $this->exportSlowCSV($output, $startDate, $endDate);
                    break;
                case 'top_clients':
                    $this->exportTopClientsCSV($output, $startDate, $endDate);
                    break;
                case 'sellers':
                    $this->exportSellersCSV($output, $startDate, $endDate);
                    break;
                case 'categories':
                    $this->exportCategoriesCSV($output, $startDate, $endDate);
                    break;
            }

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function exportSalesCSV($output, $startDate, $endDate, $request)
    {
        fputcsv($output, ['REPORTE DE VENTAS - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['FACTURA', 'FECHA', 'CLIENTE', 'TIPO CLIENTE', 'DOCUMENTO', 'CONDICION', 'SUBTOTAL', 'IVA', 'TOTAL', 'ESTADO']);

        $query = Sale::with('client')->whereBetween('date', [$startDate, $endDate]);
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        foreach ($query->cursor() as $sale) {
            fputcsv($output, [
                $sale->invoice_number ?? '#'.$sale->id,
                $sale->date->format('d/m/Y'),
                $sale->billing_name ?? $sale->client?->name,
                $sale->client?->isCompany() ? 'Empresa' : 'Persona Natural',
                $sale->billing_document_number ?? $sale->client?->document_number ?? 'N/A',
                $sale->payment_type === 'credit' ? 'Crédito' : 'Contado',
                number_format($sale->subtotal, 2),
                number_format($sale->tax_total, 2),
                number_format($sale->total, 2),
                $sale->status === 'completed' ? 'Pagada' : 'Pendiente',
            ]);
        }
    }

    private function exportPurchasesCSV($output, $startDate, $endDate, $request)
    {
        fputcsv($output, ['REPORTE DE COMPRAS - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['COMPRA', 'FECHA', 'PROVEEDOR', 'TOTAL', 'ESTADO']);

        $query = Purchase::with('supplier')->whereBetween('date', [$startDate, $endDate]);
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        foreach ($query->cursor() as $purchase) {
            fputcsv($output, [
                '#'.$purchase->id,
                $purchase->date->format('d/m/Y'),
                $purchase->supplier?->name ?? 'N/A',
                number_format($purchase->total, 2),
                $purchase->status,
            ]);
        }
    }

    private function exportInventoryCSV($output, $request)
    {
        fputcsv($output, ['REPORTE DE INVENTARIO - EsteliPOS']);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['CODIGO', 'PRODUCTO', 'CATEGORIA', 'STOCK', 'UNIT', 'PRECIO COMPRA', 'PRECIO VENTA', 'VALOR TOTAL', 'LOTE', 'VENCIMIENTO', 'UBICACION', 'ESTADO']);

        $query = Product::with('category');
        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        foreach ($query->cursor() as $product) {
            fputcsv($output, [
                $product->code,
                $product->name,
                $product->category?->name ?? 'N/A',
                $product->stock,
                $product->unit,
                number_format($product->purchase_price, 2),
                number_format($product->sale_price, 2),
                number_format($product->stock * $product->purchase_price, 2),
                $product->lot ?? 'N/A',
                $product->expiry_date?->format('d/m/Y') ?? 'N/A',
                $product->location ?? 'N/A',
                $product->status,
            ]);
        }
    }

    private function exportKardexCSV($output, $request)
    {
        fputcsv($output, ['KARDEX DE MOVIMIENTOS - EsteliPOS']);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['FECHA', 'PRODUCTO', 'TIPO', 'CANTIDAD', 'STOCK ANTES', 'STOCK DESPUES', 'REFERENCIA', 'NOTA', 'USUARIO']);

        $query = InventoryMovement::with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        foreach ($query->cursor() as $movement) {
            fputcsv($output, [
                $movement->created_at->format('d/m/Y H:i'),
                $movement->product?->name ?? 'N/A',
                $movement->type === 'in' ? 'Entrada' : 'Salida',
                $movement->quantity,
                $movement->stock_before ?? 'N/A',
                $movement->stock_after ?? 'N/A',
                $movement->reference ?? 'N/A',
                $movement->note ?? '',
                $movement->user?->name ?? 'N/A',
            ]);
        }
    }

    private function exportProfitCSV($output, $startDate, $endDate): void
    {
        fputcsv($output, ['REPORTE DE RENTABILIDAD - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['FACTURA', 'FECHA', 'TOTAL VENTA', 'COSTO', 'GANANCIA', 'MARGEN %']);

        $sales = Sale::with('details.product')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->latest()
            ->cursor();

        foreach ($sales as $sale) {
            $cost = $sale->details->sum(fn ($d) => $d->quantity * ($d->product?->purchase_price ?? 0));
            $profit = $sale->total - $cost;
            $margin = $sale->total > 0 ? ($profit / $sale->total) * 100 : 0;

            fputcsv($output, [
                $sale->invoice_number ?? '#'.$sale->id,
                $sale->date->format('d/m/Y'),
                number_format($sale->total, 2),
                number_format($cost, 2),
                number_format($profit, 2),
                number_format($margin, 1),
            ]);
        }
    }

    /**
     * @param  Collection<int, object>  $items
     */
    private function paginateCollection(Collection $items, int $perPage = 35): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * @return Collection<int, object>
     */
    private function buildAbcRows(string $startDate, string $endDate): Collection
    {
        $rows = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.code', 'products.name')
            ->orderByDesc('total')
            ->get([
                'products.code',
                'products.name',
                DB::raw('SUM(sale_details.quantity) as quantity'),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);

        $grandTotal = (float) $rows->sum('total');
        $cumulative = 0.0;

        return $rows->map(function (object $row) use ($grandTotal, &$cumulative): object {
            $total = (float) $row->total;
            $previousShare = $grandTotal > 0 ? ($cumulative / $grandTotal) * 100 : 0;
            $cumulative += $total;
            $share = $grandTotal > 0 ? ($total / $grandTotal) * 100 : 0;
            $cumulativeShare = $grandTotal > 0 ? ($cumulative / $grandTotal) * 100 : 0;

            $row->share = round($share, 1);
            $row->cumulative_share = round($cumulativeShare, 1);
            $row->class = $previousShare < 80 ? 'A' : ($previousShare < 95 ? 'B' : 'C');

            return $row;
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summarizeAbc(Collection $rows): array
    {
        $total = (float) $rows->sum('total');
        $classA = $rows->where('class', 'A');

        return [
            'sku_count' => $rows->count(),
            'class_a' => $classA->count(),
            'class_b' => $rows->where('class', 'B')->count(),
            'class_c' => $rows->where('class', 'C')->count(),
            'class_a_share' => $total > 0 ? round(((float) $classA->sum('total') / $total) * 100, 1) : 0,
            'total_sales' => $total,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function buildAgingRows(): Collection
    {
        return Sale::query()
            ->with('client')
            ->where('payment_type', 'credit')
            ->where('status', 'completed')
            ->get()
            ->map(function (Sale $sale): ?object {
                $due = max(0, (float) $sale->total - (float) $sale->amount_paid);
                if ($due < 0.01) {
                    return null;
                }

                $anchor = $sale->due_date ?? $sale->date?->copy()->addDays(30);
                $days = $anchor ? (int) $anchor->diffInDays(now(), false) : 0;
                $bucket = $days <= 0 ? 'Al día' : ($days <= 30 ? '1-30 días' : ($days <= 60 ? '31-60 días' : ($days <= 90 ? '61-90 días' : 'Más de 90')));

                return (object) [
                    'invoice' => $sale->invoice_number ?? '#'.$sale->id,
                    'client' => $sale->billing_name ?? $sale->client?->name ?? 'Cliente de crédito',
                    'due_date' => $anchor,
                    'days' => max(0, $days),
                    'bucket' => $bucket,
                    'due' => $due,
                    'total' => (float) $sale->total,
                ];
            })
            ->filter()
            ->sortByDesc('due')
            ->values();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summarizeAging(Collection $rows): array
    {
        return [
            'total_due' => (float) $rows->sum('due'),
            'current' => (float) $rows->where('bucket', 'Al día')->sum('due'),
            'overdue' => (float) $rows->where('bucket', '!=', 'Al día')->sum('due'),
            'over_90' => (float) $rows->where('bucket', 'Más de 90')->sum('due'),
            'count' => $rows->count(),
        ];
    }

    private function soldProductIds(string $startDate, string $endDate): Collection
    {
        return DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->distinct()
            ->pluck('sale_details.product_id');
    }

    private function getSlowMoversReport(string $startDate, string $endDate): LengthAwarePaginator
    {
        $soldIds = $this->soldProductIds($startDate, $endDate);

        return Product::query()
            ->with('category')
            ->where('stock', '>', 0)
            ->when($soldIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $soldIds))
            ->orderByDesc(DB::raw('stock * purchase_price'))
            ->paginate(35);
    }

    /**
     * @return array<string, float|int>
     */
    private function getSlowMoversSummary(string $startDate, string $endDate): array
    {
        $soldIds = $this->soldProductIds($startDate, $endDate);
        $query = Product::query()->where('stock', '>', 0)
            ->when($soldIds->isNotEmpty(), fn ($builder) => $builder->whereNotIn('id', $soldIds));

        return [
            'count' => (clone $query)->count(),
            'tied_value' => (float) (clone $query)->selectRaw('SUM(stock * purchase_price) as value')->value('value'),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function buildTopClientRows(string $startDate, string $endDate): Collection
    {
        return DB::table('sales')
            ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('sales.client_id', 'clients.name')
            ->orderByDesc('total')
            ->get([
                DB::raw("COALESCE(clients.name, 'Consumidor final') as name"),
                DB::raw('COUNT(sales.id) as tickets'),
                DB::raw('SUM(sales.total) as total'),
                DB::raw('AVG(sales.total) as average'),
            ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summarizeTopClients(Collection $rows): array
    {
        $total = (float) $rows->sum('total');
        $top = (float) $rows->take(5)->sum('total');

        return [
            'clients' => $rows->count(),
            'total_sales' => $total,
            'top_share' => $total > 0 ? round(($top / $total) * 100, 1) : 0,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function buildSellerRows(string $startDate, string $endDate): Collection
    {
        return DB::table('sales')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('sales.user_id', 'users.name')
            ->orderByDesc('total')
            ->get([
                DB::raw("COALESCE(users.name, 'Caja / mostrador') as name"),
                DB::raw('COUNT(sales.id) as tickets'),
                DB::raw('SUM(sales.total) as total'),
                DB::raw('AVG(sales.total) as average'),
            ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summarizeSellers(Collection $rows): array
    {
        return [
            'sellers' => $rows->count(),
            'tickets' => (int) $rows->sum('tickets'),
            'total_sales' => (float) $rows->sum('total'),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function buildCategoryRows(string $startDate, string $endDate): Collection
    {
        $rows = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get([
                DB::raw("COALESCE(categories.name, 'Sin categoría') as name"),
                DB::raw('SUM(sale_details.quantity) as quantity'),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);

        $grandTotal = (float) $rows->sum('total');

        return $rows->map(function (object $row) use ($grandTotal): object {
            $row->share = $grandTotal > 0 ? round(((float) $row->total / $grandTotal) * 100, 1) : 0;

            return $row;
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function summarizeCategories(Collection $rows): array
    {
        return [
            'categories' => $rows->count(),
            'total_sales' => (float) $rows->sum('total'),
            'top_category' => $rows->first()?->name ?? '—',
        ];
    }

    private function exportAbcCSV($output, string $startDate, string $endDate): void
    {
        fputcsv($output, ['ANALISIS ABC - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, []);
        fputcsv($output, ['CLASE', 'CODIGO', 'PRODUCTO', 'CANTIDAD', 'VENTAS', 'PARTICIPACION %', 'ACUMULADO %']);

        foreach ($this->buildAbcRows($startDate, $endDate) as $row) {
            fputcsv($output, [$row->class, $row->code, $row->name, $row->quantity, number_format((float) $row->total, 2), $row->share, $row->cumulative_share]);
        }
    }

    private function exportAgingCSV($output): void
    {
        fputcsv($output, ['ANTIGUEDAD DE CARTERA - EsteliPOS']);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['FACTURA', 'CLIENTE', 'VENCIMIENTO', 'DIAS', 'TRAMO', 'SALDO']);

        foreach ($this->buildAgingRows() as $row) {
            fputcsv($output, [
                $row->invoice,
                $row->client,
                $row->due_date?->format('d/m/Y') ?? '—',
                $row->days,
                $row->bucket,
                number_format((float) $row->due, 2),
            ]);
        }
    }

    private function exportSlowCSV($output, string $startDate, string $endDate): void
    {
        fputcsv($output, ['PRODUCTOS DE LENTA ROTACION - EsteliPOS']);
        fputcsv($output, ['Periodo sin ventas:', $startDate.' al '.$endDate]);
        fputcsv($output, []);
        fputcsv($output, ['CODIGO', 'PRODUCTO', 'CATEGORIA', 'STOCK', 'VALOR']);

        $soldIds = $this->soldProductIds($startDate, $endDate);
        $products = Product::query()
            ->with('category')
            ->where('stock', '>', 0)
            ->when($soldIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $soldIds))
            ->orderByDesc(DB::raw('stock * purchase_price'))
            ->cursor();

        foreach ($products as $product) {
            fputcsv($output, [
                $product->code,
                $product->name,
                $product->category?->name ?? 'N/A',
                $product->stock,
                number_format($product->stock * $product->purchase_price, 2),
            ]);
        }
    }

    private function exportTopClientsCSV($output, string $startDate, string $endDate): void
    {
        fputcsv($output, ['CLIENTES TOP - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, []);
        fputcsv($output, ['CLIENTE', 'TICKETS', 'TOTAL', 'PROMEDIO']);

        foreach ($this->buildTopClientRows($startDate, $endDate) as $row) {
            fputcsv($output, [$row->name, $row->tickets, number_format((float) $row->total, 2), number_format((float) $row->average, 2)]);
        }
    }

    private function exportSellersCSV($output, string $startDate, string $endDate): void
    {
        fputcsv($output, ['DESEMPEÑO DE VENDEDORES - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, []);
        fputcsv($output, ['VENDEDOR', 'TICKETS', 'TOTAL', 'PROMEDIO']);

        foreach ($this->buildSellerRows($startDate, $endDate) as $row) {
            fputcsv($output, [$row->name, $row->tickets, number_format((float) $row->total, 2), number_format((float) $row->average, 2)]);
        }
    }

    private function exportCategoriesCSV($output, string $startDate, string $endDate): void
    {
        fputcsv($output, ['VENTAS POR CATEGORIA - EsteliPOS']);
        fputcsv($output, ['Periodo:', $startDate.' al '.$endDate]);
        fputcsv($output, []);
        fputcsv($output, ['CATEGORIA', 'CANTIDAD', 'TOTAL', 'PARTICIPACION %']);

        foreach ($this->buildCategoryRows($startDate, $endDate) as $row) {
            fputcsv($output, [$row->name, $row->quantity, number_format((float) $row->total, 2), $row->share]);
        }
    }
}

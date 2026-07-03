<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Client;
use App\Models\Supplier;
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

        return $query->latest()->paginate(50);
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

        return $query->latest()->paginate(50);
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

        return $query->orderBy('name')->paginate(50);
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

        return $query->latest()->paginate(100);
    }

    private function getProfitReport($startDate, $endDate)
    {
        return Sale::with('details.product')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->latest()
            ->paginate(50);
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

        $filename = "reporte_{$reportType}_" . Carbon::now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($reportType, $startDate, $endDate, $request) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8

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
            }

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function exportSalesCSV($output, $startDate, $endDate, $request)
    {
        fputcsv($output, ['REPORTE DE VENTAS - Agroservicio S.A.']);
        fputcsv($output, ['Periodo:', $startDate . ' al ' . $endDate]);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['FACTURA', 'FECHA', 'CLIENTE', 'RUC', 'CONDICION', 'SUBTOTAL', 'IVA', 'TOTAL', 'ESTADO']);

        $query = Sale::with('client')->whereBetween('date', [$startDate, $endDate]);
        if ($request->filled('client_id')) $query->where('client_id', $request->client_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        foreach ($query->cursor() as $sale) {
            fputcsv($output, [
                $sale->invoice_number ?? '#' . $sale->id,
                $sale->date->format('d/m/Y'),
                $sale->billing_name ?? $sale->client?->name,
                $sale->billing_ruc ?? $sale->client?->ruc ?? 'N/A',
                $sale->payment_type === 'credit' ? 'Crédito' : 'Contado',
                number_format($sale->subtotal, 2),
                number_format($sale->tax_total, 2),
                number_format($sale->total, 2),
                $sale->status === 'completed' ? 'Pagada' : 'Pendiente'
            ]);
        }
    }

    private function exportPurchasesCSV($output, $startDate, $endDate, $request)
    {
        fputcsv($output, ['REPORTE DE COMPRAS - Agroservicio S.A.']);
        fputcsv($output, ['Periodo:', $startDate . ' al ' . $endDate]);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['COMPRA', 'FECHA', 'PROVEEDOR', 'TOTAL', 'ESTADO']);

        $query = Purchase::with('supplier')->whereBetween('date', [$startDate, $endDate]);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);

        foreach ($query->cursor() as $purchase) {
            fputcsv($output, [
                '#' . $purchase->id,
                $purchase->date->format('d/m/Y'),
                $purchase->supplier?->name ?? 'N/A',
                number_format($purchase->total, 2),
                $purchase->status
            ]);
        }
    }

    private function exportInventoryCSV($output, $request)
    {
        fputcsv($output, ['REPORTE DE INVENTARIO - Agroservicio S.A.']);
        fputcsv($output, ['Generado:', Carbon::now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['CODIGO', 'PRODUCTO', 'CATEGORIA', 'STOCK', 'UNIT', 'PRECIO COMPRA', 'PRECIO VENTA', 'VALOR TOTAL', 'LOTE', 'VENCIMIENTO', 'UBICACION', 'ESTADO']);

        $query = Product::with('category');
        if ($request->filled('product_id')) $query->where('id', $request->product_id);

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
                $product->status
            ]);
        }
    }

    private function exportKardexCSV($output, $request)
    {
        fputcsv($output, ['KARDEX DE MOVIMIENTOS - Agroservicio S.A.']);
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
                $movement->user?->name ?? 'N/A'
            ]);
        }
    }
}

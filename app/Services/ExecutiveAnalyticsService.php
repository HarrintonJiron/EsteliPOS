<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payroll;
use App\Models\PerformanceEvaluation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExecutiveAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $today = Carbon::today();
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $previousStart = $start->copy()->subMonth();
        $previousEnd = $start->copy()->subDay();

        $salesMonth = (float) Sale::query()->whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total');
        $salesPrevious = (float) Sale::query()->whereBetween('date', [$previousStart, $previousEnd])->where('status', 'completed')->sum('total');
        $purchasesMonth = (float) Purchase::query()->whereBetween('date', [$start, $end])->whereNotIn('status', ['canceled', 'cancelled'])->sum('total');
        $inventoryValue = (float) Product::query()->selectRaw('SUM(stock * purchase_price) as value')->value('value');
        $receivables = (float) Sale::query()->where('payment_type', 'credit')->where('status', 'completed')->selectRaw('SUM(total - amount_paid) as due')->value('due');
        $payrollNet = (float) Payroll::query()->where('year', $start->year)->where('month', $start->format('m'))->sum('net_salary');

        $trend = collect(range(5, 0))->map(function (int $monthsAgo) {
            $cursor = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => ucfirst($cursor->locale('es')->translatedFormat('M')),
                'sales' => (float) Sale::query()->whereBetween('date', [$cursor, $cursor->copy()->endOfMonth()])->where('status', 'completed')->sum('total'),
                'purchases' => (float) Purchase::query()->whereBetween('date', [$cursor, $cursor->copy()->endOfMonth()])->whereNotIn('status', ['canceled', 'cancelled'])->sum('total'),
            ];
        });

        $categories = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get([
                DB::raw("COALESCE(categories.name, 'Sin categoría') as name"),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);

        $topProducts = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.code')
            ->orderByDesc('total')
            ->limit(8)
            ->get([
                'products.name',
                'products.code',
                DB::raw('SUM(sale_details.quantity) as quantity'),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);

        $tickets = (int) Sale::query()->whereBetween('date', [$start, $end])->where('status', 'completed')->count();
        $costOfGoodsSold = (float) DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', 'completed')
            ->selectRaw('SUM(COALESCE(sale_details.base_quantity, sale_details.quantity) * products.purchase_price) as total')
            ->value('total');
        $netSales = (float) DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', 'completed')
            ->sum('sale_details.subtotal');
        $margin = $netSales - $costOfGoodsSold;
        $mix = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw("SUM(CASE WHEN payment_type = 'credit' THEN total ELSE 0 END) as credit_total")
            ->selectRaw("SUM(CASE WHEN payment_type = 'credit' THEN 0 ELSE total END) as cash_total")
            ->first();
        $cashMonth = (float) ($mix->cash_total ?? 0);
        $creditMonth = (float) ($mix->credit_total ?? 0);

        $from = now()->subDays(13)->startOfDay();
        $salesByDay = Sale::query()
            ->where('status', 'completed')
            ->where('date', '>=', $from->toDateString())
            ->get(['date', 'total'])
            ->groupBy(fn (Sale $sale) => optional($sale->date)->toDateString())
            ->map(fn ($rows) => (float) $rows->sum('total'));

        $daily = collect(range(13, 0))->map(function (int $ago) use ($salesByDay): array {
            $day = now()->subDays($ago);

            return [
                'label' => $day->format('d'),
                'sales' => (float) ($salesByDay[$day->toDateString()] ?? 0),
            ];
        })->values();

        $topClients = DB::table('sales')
            ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('sales.client_id', 'clients.name')
            ->orderByDesc(DB::raw('SUM(sales.total)'))
            ->limit(5)
            ->get([
                DB::raw("COALESCE(clients.name, 'Consumidor final') as name"),
                DB::raw('COUNT(sales.id) as tickets'),
                DB::raw('SUM(sales.total) as total'),
            ]);

        $alerts = [];
        $lowStock = Product::query()->whereColumn('stock', '<=', 'low_stock_threshold')->count();
        if ($lowStock > 0) {
            $alerts[] = ['type' => 'warning', 'message' => "{$lowStock} productos en stock mínimo", 'link' => route('inventario.dashboard')];
        }
        $overdueRow = Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'completed')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->selectRaw('COUNT(*) as overdue_count, SUM(total - amount_paid) as due')
            ->first();
        $overdue = (int) ($overdueRow->overdue_count ?? 0);
        $overdueAmount = (float) ($overdueRow->due ?? 0);
        if ($overdue > 0) {
            $alerts[] = ['type' => 'danger', 'message' => "{$overdue} créditos vencidos por cobrar", 'link' => route('creditos.overdue')];
        }

        $salesChange = $salesPrevious > 0 ? round((($salesMonth - $salesPrevious) / $salesPrevious) * 100, 1) : 0;

        return [
            'period_label' => ucfirst(now()->locale('es')->translatedFormat('F Y')),
            'generated_at' => now()->format('d/m/Y H:i'),
            'kpis' => [
                'sales_today' => (float) Sale::query()->whereDate('date', $today)->where('status', 'completed')->sum('total'),
                'sales_month' => $salesMonth,
                'sales_change' => $salesChange,
                'purchases_month' => $purchasesMonth,
                'margin' => $margin,
                'margin_pct' => $netSales > 0 ? round(($margin / $netSales) * 100, 1) : 0,
                'ticket_avg' => $tickets > 0 ? $salesMonth / $tickets : 0,
                'inventory_value' => $inventoryValue,
                'receivables' => max(0, $receivables),
                'overdue_amount' => max(0, $overdueAmount),
                'payroll_net' => $payrollNet,
                'clients' => Client::query()->count(),
                'tickets' => $tickets,
                'cash_month' => $cashMonth,
                'credit_month' => $creditMonth,
                'cash_share' => ($cashMonth + $creditMonth) > 0 ? round(($cashMonth / ($cashMonth + $creditMonth)) * 100, 1) : 0,
                'low_stock' => $lowStock,
                'overdue_count' => $overdue,
            ],
            'trend' => $trend,
            'daily' => $daily,
            'categories' => $categories,
            'top_products' => $topProducts,
            'top_clients' => $topClients,
            'branches' => $this->branchScorecard(),
            'cost_centers' => $this->costCenterScorecard(now()->startOfYear(), $end),
            'alerts' => $alerts,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function branchScorecard(): Collection
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        if (! Schema::hasTable('branches')) {
            return collect();
        }

        $branches = Branch::query()->with(['warehouse', 'costCenter'])->orderBy('code')->get();
        if ($branches->isEmpty()) {
            return collect();
        }

        return $branches->map(function (Branch $branch) use ($start, $end) {
            $warehouseSales = $branch->warehouse_id
                ? (float) Sale::query()->where('warehouse_id', $branch->warehouse_id)->whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total')
                : 0;
            $stockValue = $branch->warehouse_id
                ? (float) WarehouseStock::query()
                    ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                    ->where('warehouse_stocks.warehouse_id', $branch->warehouse_id)
                    ->selectRaw('SUM(warehouse_stocks.quantity * COALESCE(warehouse_stocks.purchase_price, products.purchase_price)) as value')
                    ->value('value')
                : 0;

            return [
                'branch' => $branch,
                'sales_month' => $warehouseSales,
                'stock_value' => $stockValue,
                'employees' => Schema::hasColumn('employees', 'branch_id')
                    ? ($branch->employees()->count() ?: Employee::query()->where('branch_id', $branch->id)->count())
                    : 0,
                'share' => (int) $branch->share_percent,
            ];
        });
    }

    /**
     * @return Collection<int, object>
     */
    public function costCenterScorecard(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start ??= now()->startOfYear();
        $end ??= now();

        $posted = JournalEntry::STATUS_POSTED;

        return JournalEntryLine::query()
            ->selectRaw('cost_centers.id, cost_centers.code, cost_centers.name, cost_centers.type, SUM(journal_entry_lines.debit) as debit, SUM(journal_entry_lines.credit) as credit')
            ->join('cost_centers', 'cost_centers.id', '=', 'journal_entry_lines.cost_center_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', $posted)
            ->whereBetween('journal_entries.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('cost_centers.id', 'cost_centers.code', 'cost_centers.name', 'cost_centers.type')
            ->orderBy('cost_centers.code')
            ->get()
            ->map(function ($row) {
                $row->net = round((float) $row->debit - (float) $row->credit, 2);
                $row->type_label = CostCenter::TYPES[$row->type] ?? $row->type;

                return $row;
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function humanResources(): array
    {
        $employees = Employee::query()
            ->when(Schema::hasTable('branches') && Schema::hasColumn('employees', 'branch_id'), fn ($query) => $query->with('branch'))
            ->orderBy('name')
            ->get();
        $payroll = app(PayrollService::class);

        $directory = $employees->map(function (Employee $employee) use ($payroll) {
            $benefits = $payroll->calculateBenefits($employee);
            $vacation = $payroll->calculateVacationBalance($employee);

            return [
                'employee' => $employee,
                'benefits' => $benefits,
                'vacation' => $vacation,
            ];
        });

        $organigram = $employees->groupBy('position');
        $shifts = [
            'completo' => ['label' => 'Turno completo', 'hours' => '07:30 — 17:00', 'days' => 'Lun a Sáb'],
            'mostrador' => ['label' => 'Mostrador / caja', 'hours' => '08:00 — 18:00', 'days' => 'Lun a Sáb'],
            'bodega' => ['label' => 'Bodega y patio', 'hours' => '07:00 — 16:00', 'days' => 'Lun a Sáb'],
            'taller' => ['label' => 'Taller técnico', 'hours' => '08:00 — 17:00', 'days' => 'Lun a Vie'],
        ];

        $attendanceDays = collect();
        $cursor = now()->startOfDay();
        while ($attendanceDays->count() < 12) {
            if ($cursor->isWeekday()) {
                $attendanceDays->push($cursor->copy());
            }
            $cursor->subDay();
        }
        $attendanceDays = $attendanceDays->reverse()->values();

        $attendanceRecords = AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('work_date', [$attendanceDays->first(), $attendanceDays->last()])
            ->get()->keyBy(fn (AttendanceRecord $record) => $record->employee_id.'-'.$record->work_date->toDateString());

        $attendance = $employees->map(function (Employee $employee) use ($attendanceDays, $attendanceRecords) {
            $days = $attendanceDays->map(function (Carbon $day) use ($employee, $attendanceRecords) {
                $record = $attendanceRecords->get($employee->id.'-'.$day->toDateString());

                return ['date' => $day, 'status' => $record?->status, 'record' => $record];
            });

            return [
                'employee' => $employee,
                'days' => $days,
                'present' => $days->where('status', 'present')->count(),
                'late' => $days->where('status', 'late')->count(),
                'absent' => $days->where('status', 'absent')->count(),
            ];
        });

        $latestEvaluations = PerformanceEvaluation::query()->with(['employee.branch', 'evaluator'])
            ->whereIn('employee_id', $employees->pluck('id'))->latest('evaluation_date')->latest('id')->get()
            ->unique('employee_id')->values();
        $evaluations = $latestEvaluations->map(fn (PerformanceEvaluation $evaluation) => [
            'employee' => $evaluation->employee,
            'score' => $evaluation->score,
            'label' => $evaluation->label,
            'evaluation' => $evaluation,
        ]);

        $inss = Payroll::query()
            ->with('employee')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(24)
            ->get();

        return [
            'employees' => $employees,
            'directory' => $directory,
            'organigram' => $organigram,
            'shifts' => $shifts,
            'attendance' => $attendance,
            'attendance_days' => $attendanceDays,
            'evaluations' => $evaluations,
            'inss' => $inss,
            'thirteenth' => $directory,
        ];
    }
}

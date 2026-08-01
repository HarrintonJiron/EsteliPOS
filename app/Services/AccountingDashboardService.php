<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;

class AccountingDashboardService
{
    public function __construct(
        private ReportService $reports,
        private LedgerService $ledger,
    ) {
    }

    public function build(?string $month = null): array
    {
        $period = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        $dateFrom = $period->toDateString();
        $dateTo = $period->copy()->endOfMonth()->toDateString();
        $income = $this->reports->incomeStatement($dateFrom, $dateTo);

        $balance = fn (string $code) => round((float) Account::where('code', $code)->get()
            ->sum(fn (Account $account) => $this->ledger->openingBalance($account, $dateTo < now()->toDateString()
                ? Carbon::parse($dateTo)->addDay()->toDateString()
                : now()->addDay()->toDateString())), 2);

        $sales = (float) Sale::whereNotIn('status', ['canceled', 'cancelled'])
            ->whereBetween('date', [$dateFrom, $dateTo])->sum('total');
        $purchases = (float) Purchase::whereNotIn('status', ['canceled', 'cancelled'])
            ->whereBetween('date', [$dateFrom, $dateTo])->sum('total');

        $chart = collect(range(11, 0))->map(function (int $monthsAgo) use ($period) {
            $cursor = $period->copy()->subMonths($monthsAgo);
            $report = $this->reports->incomeStatement(
                $cursor->copy()->startOfMonth()->toDateString(),
                $cursor->copy()->endOfMonth()->toDateString(),
            );

            return [
                'label' => ucfirst($cursor->locale('es')->translatedFormat('M y')),
                'income' => $report['totalIngresos'] + $report['totalOtrosIngresos'],
                'expenses' => $report['totalCostos'] + $report['totalGastos'] + $report['totalOtrosGastos'],
                'profit' => $report['utilidadNeta'],
            ];
        });

        return [
            'month' => $period->format('Y-m'),
            'periodLabel' => ucfirst($period->locale('es')->translatedFormat('F Y')),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sales' => round($sales, 2),
            'purchases' => round($purchases, 2),
            'income' => round($income['totalIngresos'] + $income['totalOtrosIngresos'], 2),
            'expenses' => round($income['totalCostos'] + $income['totalGastos'] + $income['totalOtrosGastos'], 2),
            'profit' => $income['utilidadNeta'],
            'cash' => $balance('1.1.01'),
            'bank' => $balance('1.1.02'),
            'receivables' => $balance('1.1.04'),
            'payables' => $balance('2.1.01'),
            'capital' => $balance('3.1'),
            'chart' => $chart,
        ];
    }
}

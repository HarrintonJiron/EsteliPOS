<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Services\PeriodClosingService;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    public function __construct(private PeriodClosingService $closingService)
    {
    }

    private const MONTHS = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', FiscalPeriod::class);
        $year = (int) ($request->year ?? now()->year);

        for ($month = 1; $month <= 12; $month++) {
            FiscalPeriod::forMonth($year, $month);
        }
        $annual = FiscalPeriod::forYear($year);

        $months = FiscalPeriod::monthly()->where('year', $year)->orderBy('month')->get();
        $pendingDrafts = $months->mapWithKeys(fn (FiscalPeriod $period) => [
            $period->id => $this->closingService->pendingDrafts($period),
        ]);
        $closingEntry = $this->closingService->closingEntry($annual);
        $canCloseAnnual = $months->every(fn (FiscalPeriod $period) => $period->status === FiscalPeriod::STATUS_CLOSED)
            && $pendingDrafts->sum() === 0;

        return view('contabilidad.periodos.index', [
            'year' => $year,
            'months' => $months,
            'annual' => $annual,
            'monthNames' => self::MONTHS,
            'pendingDrafts' => $pendingDrafts,
            'closingEntry' => $closingEntry,
            'canCloseAnnual' => $canCloseAnnual,
        ]);
    }

    public function closeMonth(Request $request, FiscalPeriod $periodo)
    {
        $this->authorize('close', $periodo);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->closingService->closeMonth($periodo, $request->user(), $data['notes'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Período {$periodo->month}/{$periodo->year} cerrado correctamente.");
    }

    public function reopenMonth(Request $request, FiscalPeriod $periodo)
    {
        $this->authorize('reopen', $periodo);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->closingService->reopenMonth($periodo, $request->user(), $data['notes'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Período {$periodo->month}/{$periodo->year} reabierto correctamente.");
    }

    public function closeYear(Request $request, FiscalPeriod $periodo)
    {
        $this->authorize('close', $periodo);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $entry = $this->closingService->closeYear($periodo, $request->user(), $data['notes'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = "Cierre anual {$periodo->year} realizado correctamente.";
        if ($entry) $message .= " Se generó el asiento {$entry->number}.";
        return back()->with('success', $message);
    }

    public function reopenYear(Request $request, FiscalPeriod $periodo)
    {
        $this->authorize('reopen', $periodo);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->closingService->reopenYear($periodo, $request->user(), $data['notes'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cierre anual {$periodo->year} reabierto correctamente.");
    }
}

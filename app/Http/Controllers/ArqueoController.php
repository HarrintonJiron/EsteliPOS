<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenCashRegisterRequest;
use App\Models\Arqueo;
use App\Models\CajaSession;
use App\Models\CreditPayment;
use App\Models\OperationalExpense;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArqueoController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();
        $today = Carbon::today()->toDateString();

        $openSession = CajaSession::query()
            ->with('openedBy')
            ->whereDate('date', $today)
            ->where('status', 'open')
            ->first();

        $closingSummary = null;

        if ($openSession) {
            $cashSalesTotal = (float) Sale::query()
                ->whereDate('date', $today)
                ->where('status', 'completed')
                ->where('payment_type', 'cash')
                ->sum('total');

            $operationalExpensesCashTotal = (float) OperationalExpense::query()
                ->registered()
                ->cash()
                ->whereDate('expense_date', $today)
                ->sum('amount');

            $creditPaymentsTotal = (float) CreditPayment::query()
                ->whereDate('payment_date', $today)
                ->sum('amount');

            $salesCount = (int) Sale::query()
                ->whereDate('date', $today)
                ->where('status', 'completed')
                ->count();

            $openingAmount = (float) $openSession->opening_amount;
            $expectedCashTotal = $openingAmount + $cashSalesTotal - $operationalExpensesCashTotal;

            $closingSummary = [
                'opening_amount' => $openingAmount,
                'cash_sales_total' => $cashSalesTotal,
                'operational_expenses_cash_total' => $operationalExpensesCashTotal,
                'credit_payments_total' => $creditPaymentsTotal,
                'sales_count' => $salesCount,
                'expected_cash_total' => $expectedCashTotal,
            ];
        }

        return view('arqueo.wait', [
            'now' => $now,
            'openSession' => $openSession,
            'closingSummary' => $closingSummary,
            'denominations' => [1000, 500, 200, 100, 50, 20, 10, 5, 1],
        ]);
    }

    public function open(OpenCashRegisterRequest $request): RedirectResponse
    {
        $date = Carbon::today();
        $existing = CajaSession::query()->where('status', 'open')->first();

        if ($existing) {
            return redirect()
                ->route('arqueo.index')
                ->with('warning', 'La caja ya se encuentra abierta.');
        }

        try {
            DB::transaction(function () use ($request, $date): void {
                CajaSession::query()->create([
                    'date' => $date->toDateString(),
                    'opened_at' => Carbon::now(),
                    'opened_by' => $request->user()?->id,
                    'opening_amount' => $request->validated('opening_amount'),
                    'status' => 'open',
                    'open_guard' => 'OPEN',
                ]);
            });
        } catch (QueryException $exception) {
            if (CajaSession::query()->where('status', 'open')->exists()) {
                return redirect()
                    ->route('arqueo.index')
                    ->with('warning', 'La caja ya fue abierta desde otra computadora o tablet.');
            }

            throw $exception;
        }

        $user = $request->user();
        $canOpenPos = $user && (
            $user->isAdmin()
            || $user->hasPermission('ventas.view')
            || $user->hasPermission('ventas.create')
        );

        if ($canOpenPos && Route::has('facturacion.pos')) {
            return redirect()
                ->route('facturacion.pos')
                ->with('success', 'Caja abierta. Ya puede comenzar a vender.');
        }

        return redirect()
            ->route('arqueo.index')
            ->with('success', 'Caja abierta correctamente.');
    }

    public function run(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'caja_session_id' => ['required', 'integer', 'exists:caja_sessions,id'],
            'physical_counts' => ['nullable', 'array'],
        ]);
        $date = Carbon::parse($validated['date']);
        $physicalCounts = $validated['physical_counts'] ?? [];

        $result = DB::transaction(function () use ($request, $date, $physicalCounts): array {
            $cajaSession = CajaSession::query()
                ->whereKey($request->integer('caja_session_id'))
                ->lockForUpdate()
                ->firstOrFail();

            if ($cajaSession->status !== 'open' || $cajaSession->open_guard !== 'OPEN') {
                throw ValidationException::withMessages([
                    'caja_session_id' => 'Esta caja ya fue cerrada desde otra computadora o tablet.',
                ]);
            }

            if (! $cajaSession->date->isSameDay($date)) {
                throw ValidationException::withMessages([
                    'date' => 'La fecha del arqueo debe coincidir con la fecha de apertura de la caja.',
                ]);
            }

            $sales = Sale::query()
                ->whereDate('date', $date->toDateString())
                ->where('status', 'completed')
                ->with('client', 'details')
                ->get();

            $totalSalesCount = $sales->count();
            $totalSalesAmount = $sales->sum('total');

            $byType = $sales->groupBy('payment_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            });

            $creditPayments = CreditPayment::whereDate('payment_date', $date->toDateString())->with('client')->get();
            $creditPaymentsTotal = $creditPayments->sum('amount');

            $operationalExpenses = OperationalExpense::query()
                ->with(['user', 'cajaSession'])
                ->registered()
                ->cash()
                ->whereDate('expense_date', $date->toDateString())
                ->get();
            $operationalExpensesCashTotal = (float) $operationalExpenses->sum('amount');

            $physicalTotal = 0;
            foreach ($physicalCounts as $count) {
                $amount = (float) str_replace(',', '', (string) ($count['amount'] ?? 0));
                $qty = (float) str_replace(',', '', (string) ($count['qty'] ?? 0));
                if ($amount < 0 || $qty < 0) {
                    throw ValidationException::withMessages([
                        'physical_counts' => 'El conteo físico no puede contener valores negativos.',
                    ]);
                }
                $physicalTotal += $amount * $qty;
            }

            $openingAmount = (float) $cajaSession->opening_amount;
            $cashMovementsTotal = (float) ($byType['cash']['total'] ?? 0) - $operationalExpensesCashTotal;
            $cashTotal = $openingAmount + $cashMovementsTotal;
            $difference = $physicalTotal - $cashTotal;

            $arqueo = Arqueo::create([
                'date' => $date->toDateString(),
                'user_id' => $request->user()?->id,
                'caja_session_id' => $cajaSession->id,
                'total_sales_count' => $totalSalesCount,
                'total_sales_amount' => $totalSalesAmount,
                'cash_total' => $cashTotal,
                'credit_payments_total' => $creditPaymentsTotal,
                'physical_total' => $physicalTotal,
                'difference' => $difference,
                'details' => [
                    'sales' => $sales->pluck('id')->toArray(),
                    'credit_payments' => $creditPayments->pluck('id')->toArray(),
                    'operational_expenses' => $operationalExpenses->pluck('id')->toArray(),
                    'physical_counts' => $physicalCounts,
                    'opening_amount' => $openingAmount,
                ],
            ]);

            $cajaSession->closed_at = Carbon::now();
            $cajaSession->closed_by = $request->user()?->id;
            $cajaSession->status = 'closed';
            $cajaSession->open_guard = null;
            $cajaSession->save();

            return compact(
                'sales', 'totalSalesCount', 'totalSalesAmount', 'byType', 'creditPayments',
                'creditPaymentsTotal', 'operationalExpenses', 'operationalExpensesCashTotal',
                'openingAmount', 'cashMovementsTotal', 'cashTotal', 'physicalTotal',
                'physicalCounts', 'arqueo'
            );
        });

        return view('arqueo.report', [
            'date' => $date,
            'sales' => $result['sales'],
            'totalSalesCount' => $result['totalSalesCount'],
            'totalSalesAmount' => $result['totalSalesAmount'],
            'byType' => $result['byType'],
            'creditPayments' => $result['creditPayments'],
            'creditPaymentsTotal' => $result['creditPaymentsTotal'],
            'operationalExpenses' => $result['operationalExpenses'],
            'operationalExpensesCashTotal' => $result['operationalExpensesCashTotal'],
            'openingAmount' => $result['openingAmount'],
            'cashMovementsTotal' => $result['cashMovementsTotal'],
            'expectedCashTotal' => $result['cashTotal'],
            'physicalTotal' => $result['physicalTotal'],
            'physicalCounts' => $result['physicalCounts'],
            'arqueo' => $result['arqueo'],
        ]);
    }
}

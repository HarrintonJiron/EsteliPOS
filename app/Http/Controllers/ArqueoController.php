<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenCashRegisterRequest;
use App\Models\Arqueo;
use App\Models\Branch;
use App\Models\CajaSession;
use App\Models\CreditPayment;
use App\Models\OperationalExpense;
use App\Models\Purchase;
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
            ->with(['openedBy', 'branch'])
            ->where('opened_by', request()->user()?->id)
            ->where('status', 'open')
            ->first();

        $closingSummary = null;

        if ($openSession) {
            $cashSalesTotal = (float) Sale::query()
                ->where(fn ($query) => $query->where('caja_session_id', $openSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $openSession->opened_by)->whereDate('date', $today)))
                ->where('status', 'completed')
                ->where('payment_type', 'cash')
                ->sum('total');

            $operationalExpensesCashTotal = (float) OperationalExpense::query()
                ->registered()
                ->cash()
                ->where('caja_session_id', $openSession->id)
                ->sum('amount');
            $cashPurchasesTotal = (float) Purchase::query()
                ->where('caja_session_id', $openSession->id)
                ->where('status', 'completed')
                ->where('payment_type', 'cash')
                ->sum('total');

            $creditPaymentsTotal = (float) CreditPayment::query()
                ->where(fn ($query) => $query->where('caja_session_id', $openSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $openSession->opened_by)->whereDate('payment_date', $today)))
                ->sum('amount');
            $cashCreditPaymentsTotal = (float) CreditPayment::query()
                ->where(fn ($query) => $query->where('caja_session_id', $openSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $openSession->opened_by)->whereDate('payment_date', $today)))
                ->where('payment_type', 'cash')
                ->sum('amount');

            $salesCount = (int) Sale::query()
                ->where(fn ($query) => $query->where('caja_session_id', $openSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $openSession->opened_by)->whereDate('date', $today)))
                ->where('status', 'completed')
                ->count();

            $openingAmount = (float) $openSession->opening_amount;
            $expectedCashTotal = $openingAmount + $cashSalesTotal + $cashCreditPaymentsTotal
                - $operationalExpensesCashTotal - $cashPurchasesTotal;

            $closingSummary = [
                'opening_amount' => $openingAmount,
                'cash_sales_total' => $cashSalesTotal,
                'operational_expenses_cash_total' => $operationalExpensesCashTotal,
                'cash_purchases_total' => $cashPurchasesTotal,
                'credit_payments_total' => $creditPaymentsTotal,
                'cash_credit_payments_total' => $cashCreditPaymentsTotal,
                'sales_count' => $salesCount,
                'expected_cash_total' => $expectedCashTotal,
            ];
        }

        $branchId = request()->user()?->branch_id;

        return view('arqueo.wait', [
            'now' => $now,
            'openSession' => $openSession,
            'closingSummary' => $closingSummary,
            'denominations' => [1000, 500, 200, 100, 50, 20, 10, 5, 1],
            'branches' => Branch::query()
                ->where('is_active', true)
                ->when($branchId, fn ($query) => $query->whereKey($branchId))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function open(OpenCashRegisterRequest $request): RedirectResponse
    {
        $date = Carbon::today();
        $existing = CajaSession::currentForUser($request->user()?->id);

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
                    'branch_id' => $request->validated('branch_id'),
                    'opening_amount' => $request->validated('opening_amount'),
                    'status' => 'open',
                    'open_guard' => 'OPEN',
                ]);
            });
        } catch (QueryException $exception) {
            if (CajaSession::currentForUser($request->user()?->id)) {
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

            if ($cajaSession->status !== 'open' || $cajaSession->open_guard !== 'C'.$cajaSession->opened_by) {
                throw ValidationException::withMessages([
                    'caja_session_id' => 'Esta caja ya fue cerrada desde otra computadora o tablet.',
                ]);
            }

            if ($cajaSession->opened_by !== $request->user()?->id && ! $request->user()?->isAdmin()) {
                throw ValidationException::withMessages([
                    'caja_session_id' => 'No puede cerrar la caja de otro cajero.',
                ]);
            }

            if (! $cajaSession->date->isSameDay($date)) {
                throw ValidationException::withMessages([
                    'date' => 'La fecha del arqueo debe coincidir con la fecha de apertura de la caja.',
                ]);
            }

            $sales = Sale::query()
                ->where(fn ($query) => $query->where('caja_session_id', $cajaSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $cajaSession->opened_by)->whereDate('date', $date->toDateString())))
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

            $creditPayments = CreditPayment::query()
                ->where(fn ($query) => $query->where('caja_session_id', $cajaSession->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('caja_session_id')->where('user_id', $cajaSession->opened_by)->whereDate('payment_date', $date->toDateString())))
                ->with('client')->get();
            $creditPaymentsTotal = $creditPayments->sum('amount');
            $cashCreditPaymentsTotal = $creditPayments->where('payment_type', 'cash')->sum('amount');

            $operationalExpenses = OperationalExpense::query()
                ->with(['user', 'cajaSession'])
                ->registered()
                ->cash()
                ->where('caja_session_id', $cajaSession->id)
                ->get();
            $operationalExpensesCashTotal = (float) $operationalExpenses->sum('amount');
            $cashPurchases = Purchase::query()
                ->where('caja_session_id', $cajaSession->id)
                ->where('status', 'completed')
                ->where('payment_type', 'cash')
                ->get();
            $cashPurchasesTotal = (float) $cashPurchases->sum('total');

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
            $cashMovementsTotal = (float) ($byType['cash']['total'] ?? 0) + $cashCreditPaymentsTotal
                - $operationalExpensesCashTotal - $cashPurchasesTotal;
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
                    'cash_purchases' => $cashPurchases->pluck('id')->toArray(),
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
                'creditPaymentsTotal', 'cashCreditPaymentsTotal', 'operationalExpenses', 'operationalExpensesCashTotal',
                'cashPurchases', 'cashPurchasesTotal',
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
            'cashPurchases' => $result['cashPurchases'],
            'cashPurchasesTotal' => $result['cashPurchasesTotal'],
            'openingAmount' => $result['openingAmount'],
            'cashMovementsTotal' => $result['cashMovementsTotal'],
            'expectedCashTotal' => $result['cashTotal'],
            'physicalTotal' => $result['physicalTotal'],
            'physicalCounts' => $result['physicalCounts'],
            'arqueo' => $result['arqueo'],
        ]);
    }
}

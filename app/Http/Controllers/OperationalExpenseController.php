<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationalExpenseRequest;
use App\Models\Account;
use App\Models\CajaSession;
use App\Models\OperationalExpense;
use App\Models\RepairOrder;
use App\Services\OperationalExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalExpenseController extends Controller
{
    public function __construct(private readonly OperationalExpenseService $service)
    {
    }

    public function index(Request $request): View
    {
        $query = OperationalExpense::query()
            ->with(['user', 'cajaSession', 'repairOrder', 'account'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($inner) use ($search) {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->paginate(15)->withQueryString();
        $currentMonth = now()->startOfMonth();
        $monthExpenses = OperationalExpense::registered()->whereDate('expense_date', '>=', $currentMonth)->get();
        $stats = [
            'count' => $monthExpenses->count(),
            'amount' => round((float) $monthExpenses->sum('amount'), 2),
            'cash' => round((float) $monthExpenses->where('payment_method', 'cash')->sum('amount'), 2),
            'cancelled' => OperationalExpense::query()->where('status', OperationalExpense::STATUS_CANCELLED)->count(),
        ];

        return view('reparaciones.gastos.index', compact('expenses', 'stats'));
    }

    public function create(): View
    {
        return view('reparaciones.gastos.create', $this->formData());
    }

    public function store(OperationalExpenseRequest $request): RedirectResponse
    {
        try {
            $expense = $this->service->create($request->validated(), $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('reparaciones.gastos.show', $expense)->with('success', 'Gasto operativo registrado correctamente.');
    }

    public function show(OperationalExpense $operationalExpense): View
    {
        $operationalExpense->load(['user', 'cajaSession', 'repairOrder', 'account']);

        return view('reparaciones.gastos.show', [
            'expense' => $operationalExpense,
            'journalEntry' => $operationalExpense->currentJournalEntry(),
        ]);
    }

    public function edit(OperationalExpense $operationalExpense): View
    {
        $operationalExpense->load(['user', 'cajaSession', 'repairOrder', 'account']);

        return view('reparaciones.gastos.edit', $this->formData() + ['expense' => $operationalExpense]);
    }

    public function update(OperationalExpenseRequest $request, OperationalExpense $operationalExpense): RedirectResponse
    {
        try {
            $expense = $this->service->update($operationalExpense, $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('reparaciones.gastos.show', $expense)->with('success', 'Gasto operativo actualizado correctamente.');
    }

    public function destroy(OperationalExpense $operationalExpense): RedirectResponse
    {
        $this->service->cancel($operationalExpense);

        return redirect()->route('reparaciones.gastos.index')->with('success', 'Gasto operativo anulado correctamente.');
    }

    private function formData(): array
    {
        $defaultSession = CajaSession::query()
            ->whereDate('date', now()->toDateString())
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->first();

        return [
            'cajaSessions' => CajaSession::query()->orderByDesc('date')->orderByDesc('opened_at')->get(),
            'repairOrders' => RepairOrder::query()->orderByDesc('id')->limit(100)->get(['id', 'order_number', 'client_name']),
            'expenseAccounts' => Account::query()->postable()->ofMainGroup('gastos')->orderBy('code')->get(),
            'defaultCajaSession' => $defaultSession,
            'statuses' => [
                OperationalExpense::STATUS_DRAFT => 'Borrador',
                OperationalExpense::STATUS_REGISTERED => 'Registrado',
                OperationalExpense::STATUS_CANCELLED => 'Anulado',
            ],
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'transfer' => 'Transferencia',
                'card' => 'Tarjeta',
                'other' => 'Otro',
            ],
        ];
    }
}
<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CajaSession;
use App\Models\OperationalExpense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperationalExpenseService
{
    private const DEFAULT_EXPENSE_ACCOUNT = '6.1.99';

    public function __construct(private readonly AccountingService $accounting)
    {
    }

    public function create(array $data, User $actor): OperationalExpense
    {
        return DB::transaction(function () use ($data, $actor) {
            $data = $this->normalizePayload($data);

            $expense = OperationalExpense::create([
                ...$data,
                'user_id' => $actor->id,
                'account_id' => $data['account_id'] ?? $this->defaultExpenseAccount()->id,
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'expense_date' => $data['expense_date'],
                'caja_session_id' => $data['caja_session_id'],
            ]);

            $this->assertCashSessionMatchesDate($expense);

            if ($expense->status === OperationalExpense::STATUS_REGISTERED) {
                $this->accounting->recordOperationalExpense($expense);
            }

            AuditLog::log('operational_expense.created', "Gasto operativo {$expense->description} creado", $expense, null, $this->snapshot($expense));

            return $expense->fresh(['user', 'cajaSession', 'repairOrder', 'account']);
        });
    }

    public function update(OperationalExpense $expense, array $data): OperationalExpense
    {
        return DB::transaction(function () use ($expense, $data) {
            $old = $this->snapshot($expense->fresh(['user', 'cajaSession', 'repairOrder', 'account']));

            $data = $this->normalizePayload($data, $expense);

            $this->accounting->voidForSource(OperationalExpense::class, $expense->id, 'Gasto operativo actualizado');

            $expense->update([
                ...$data,
                'account_id' => $data['account_id'] ?? $expense->account_id ?? $this->defaultExpenseAccount()->id,
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'expense_date' => $data['expense_date'],
                'caja_session_id' => $data['caja_session_id'],
            ]);

            $this->assertCashSessionMatchesDate($expense);

            if ($expense->status === OperationalExpense::STATUS_REGISTERED) {
                $this->accounting->recordOperationalExpense($expense->fresh());
            }

            AuditLog::log('operational_expense.updated', "Gasto operativo {$expense->description} actualizado", $expense, $old, $this->snapshot($expense->fresh(['user', 'cajaSession', 'repairOrder', 'account'])));

            return $expense->fresh(['user', 'cajaSession', 'repairOrder', 'account']);
        });
    }

    public function cancel(OperationalExpense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $old = $this->snapshot($expense->fresh(['user', 'cajaSession', 'repairOrder', 'account']));

            $this->accounting->voidForSource(OperationalExpense::class, $expense->id, 'Gasto operativo anulado');

            $expense->update(['status' => OperationalExpense::STATUS_CANCELLED]);

            AuditLog::log('operational_expense.cancelled', "Gasto operativo {$expense->description} anulado", $expense, $old, $this->snapshot($expense->fresh(['user', 'cajaSession', 'repairOrder', 'account'])));
        });
    }

    private function defaultExpenseAccount(): Account
    {
        $account = Account::query()->where('code', self::DEFAULT_EXPENSE_ACCOUNT)->first();
        if (! $account) {
            throw new \RuntimeException('No existe la cuenta contable 6.1.99 para gastos operativos. Ejecute migraciones y seeders contables.');
        }

        return $account;
    }

    private function assertCashSessionMatchesDate(OperationalExpense $expense): void
    {
        if ($expense->payment_method !== 'cash') {
            return;
        }

        $session = CajaSession::query()->find($expense->caja_session_id);
        if (! $session) {
            throw new \RuntimeException('La sesión de caja seleccionada no existe.');
        }

        if ($session->date?->toDateString() !== $expense->expense_date?->toDateString()) {
            throw new \RuntimeException('La fecha del gasto debe coincidir con la fecha de la caja seleccionada para egresos en efectivo.');
        }
    }

    private function normalizePayload(array $data, ?OperationalExpense $expense = null): array
    {
        $expenseDate = $data['expense_date'] ?? $expense?->expense_date?->toDateString() ?? now()->toDateString();
        $paymentMethod = $data['payment_method'] ?? $expense?->payment_method ?? 'cash';
        $status = $data['status'] ?? $expense?->status ?? OperationalExpense::STATUS_REGISTERED;
        $cajaSessionId = $data['caja_session_id'] ?? $expense?->caja_session_id;

        if ($paymentMethod === 'cash' && ! $cajaSessionId) {
            $cajaSessionId = CajaSession::query()
                ->whereDate('date', $expenseDate)
                ->where('status', 'open')
                ->orderByDesc('opened_at')
                ->value('id');
        }

        if ($paymentMethod === 'cash' && ! $cajaSessionId) {
            throw new \RuntimeException('Debes abrir una caja para registrar un gasto operativo en efectivo.');
        }

        return [
            ...$data,
            'expense_date' => $expenseDate,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'caja_session_id' => $cajaSessionId,
        ];
    }

    private function snapshot(OperationalExpense $expense): array
    {
        return [
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'payment_method' => $expense->payment_method,
            'status' => $expense->status,
            'caja_session_id' => $expense->caja_session_id,
            'repair_order_id' => $expense->repair_order_id,
            'account_id' => $expense->account_id,
        ];
    }
}
<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CreditService
{
    /**
     * Calculate total mora (late-payment fee) owed by a client.
     * mora = sum of (balance * mora_rate% * overdue_days) per overdue credit
     * (sales and repairs), capped at mora_max_pct% of the principal if configured.
     */
    public function moraForClient(Client $client): float
    {
        return round((float) collect($this->moraBreakdown($client))->sum('mora'), 2);
    }

    /**
     * Returns per-credit mora details for a client (facturas y reparaciones).
     *
     * @return array<int, array<string, mixed>>
     */
    public function moraBreakdown(Client $client): array
    {
        if (! $client->mora_enabled || (float) $client->mora_rate <= 0) {
            return [];
        }

        $today = now()->startOfDay();
        $ratePerDay = (float) $client->mora_rate / 100;
        $graceDays = (int) ($client->mora_grace_days ?? 0);
        $maxPct = (float) ($client->mora_max_pct ?? 0);
        $breakdown = [];

        foreach ($this->receivables($client) as $row) {
            $dueDate = $row['due_date'];
            if (! $dueDate || ! $dueDate->copy()->startOfDay()->isBefore($today)) {
                continue;
            }

            $daysLate = (int) $today->diffInDays($dueDate->copy()->startOfDay(), false) * -1;
            $billableDays = max(0, $daysLate - $graceDays);
            $principal = $row['balance'];
            $mora = $principal * $ratePerDay * $billableDays;

            if ($maxPct > 0) {
                $mora = min($mora, $principal * $maxPct / 100);
            }

            $breakdown[] = [
                'type' => $row['type'],
                'invoice_number' => $row['ref'],
                'principal' => $principal,
                'days_late' => $daysLate,
                'billable_days' => $billableDays,
                'mora' => round($mora, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Todo lo que el cliente debe a crédito, normalizado: facturas y reparaciones
     * con su referencia, vencimiento y saldo. Es la base de mora, antigüedad y vencidos.
     *
     * @return Collection<int, array{type: string, ref: string, due_date: ?Carbon, balance: float, model: mixed}>
     */
    public function receivables(Client $client): Collection
    {
        $sales = $this->outstandingSales($client)->map(fn (array $row): array => [
            'type' => 'sale',
            'ref' => (string) $row['sale']->invoice_number,
            'due_date' => $row['sale']->due_date,
            'balance' => (float) $row['balance'],
            'model' => $row['sale'],
        ]);

        $repairs = $this->repairCredits($client)->map(fn (array $row): array => [
            'type' => 'repair',
            'ref' => (string) $row['order']->order_number,
            'due_date' => $row['order']->due_date,
            'balance' => (float) $row['balance'],
            'model' => $row['order'],
        ]);

        return $sales->concat($repairs)->values();
    }

    /**
     * Reparaciones a crédito con saldo pendiente, las más antiguas primero.
     *
     * @return Collection<int, array{order: RepairOrder, balance: float}>
     */
    public function repairCredits(Client $client): Collection
    {
        return RepairOrder::query()
            ->where('client_id', $client->id)
            ->where('payment_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->withSum('creditPayments', 'amount')
            ->orderByRaw('due_date IS NULL, due_date')
            ->orderBy('id')
            ->get()
            ->map(fn (RepairOrder $order): array => ['order' => $order, 'balance' => $order->balance()])
            ->filter(fn (array $row): bool => $row['balance'] > 0.00001)
            ->values();
    }

    public function pendingDebt(Client $client): float
    {
        return round($this->outstandingSales($client)->sum('balance') + $this->pendingRepairDebt($client), 2);
    }

    public function pendingRepairDebt(Client $client): float
    {
        return round((float) $this->repairCredits($client)->sum('balance'), 2);
    }

    /**
     * Applies payments to pending invoices deterministically: payments linked to a
     * sale are applied there first and unassigned payments are applied FIFO.
     *
     * @return Collection<int, array{sale: Sale, balance: float}>
     */
    public function outstandingSales(Client $client): Collection
    {
        $sales = Sale::query()
            ->where('client_id', $client->id)
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $payments = CreditPayment::query()
            ->where('client_id', $client->id)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get(['sale_id', 'amount']);

        $assigned = $payments->whereNotNull('sale_id')
            ->groupBy('sale_id')
            ->map(fn (Collection $rows): float => (float) $rows->sum('amount'));
        $unassigned = (float) $payments->whereNull('sale_id')->sum('amount');

        return $sales->map(function (Sale $sale) use ($assigned, &$unassigned): array {
            $balance = max(0, (float) $sale->total - (float) ($assigned[$sale->id] ?? 0));
            $applied = min($balance, $unassigned);
            $balance = round($balance - $applied, 2);
            $unassigned = round($unassigned - $applied, 2);

            return ['sale' => $sale, 'balance' => $balance];
        })->filter(fn (array $row): bool => $row['balance'] > 0.00001)->values();
    }

    public function outstandingBalanceForSale(Sale $sale): float
    {
        $client = $sale->client ?? Client::query()->findOrFail($sale->client_id);

        return (float) ($this->outstandingSales($client)
            ->first(fn (array $row): bool => $row['sale']->is($sale))['balance'] ?? 0);
    }

    public function availableCredit(Client $client): float
    {
        if (! $client->credit_enabled) {
            return 0;
        }

        if ((float) $client->credit_limit <= 0) {
            return PHP_FLOAT_MAX;
        }

        return max(0, round((float) $client->credit_limit - $this->pendingDebt($client), 2));
    }

    public function canGrantCredit(Client $client, float $amount): bool
    {
        if (! $client->credit_enabled) {
            return false;
        }

        if ((float) $client->credit_limit <= 0) {
            return true;
        }

        return $this->pendingDebt($client) + $amount <= (float) $client->credit_limit;
    }

    public function dueDateForClient(Client $client): string
    {
        $days = max(1, (int) ($client->credit_days ?? 30));

        return now()->addDays($days)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function clientCreditSummary(Client $client): array
    {
        // Se consulta cada cartera una sola vez (facturas y reparaciones) y de ahí salen saldo y disponible.
        $repairs = $this->repairCredits($client);
        $repairsBalance = round((float) $repairs->sum('balance'), 2);
        $salesBalance = round((float) $this->outstandingSales($client)->sum('balance'), 2);
        $balance = round($salesBalance + $repairsBalance, 2);
        $limit = (float) $client->credit_limit;

        return [
            'balance' => $balance,
            'sales_balance' => $salesBalance,
            'repairs_balance' => $repairsBalance,
            'repairs_count' => $repairs->count(),
            'credit_limit' => $limit,
            'available_credit' => $client->credit_enabled ? ($limit > 0 ? max(0, round($limit - $balance, 2)) : null) : 0,
            'credit_enabled' => (bool) $client->credit_enabled,
            'credit_days' => (int) ($client->credit_days ?? 30),
            'over_limit' => $client->credit_enabled && $limit > 0 && $balance > $limit,
            'usage_percent' => $limit > 0 ? min(100, round(($balance / $limit) * 100, 1)) : 0,
            'mora' => $this->moraForClient($client),
            'mora_enabled' => (bool) $client->mora_enabled,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function clientsWithDebt(?string $search = null): Collection
    {
        $query = Client::query()->where('credit_enabled', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get()->map(function (Client $client) {
            $summary = $this->clientCreditSummary($client);
            $totalDebt = (float) Sale::query()
                ->where('client_id', $client->id)
                ->where('payment_type', 'credit')
                ->where('status', 'pending')
                ->sum('total');
            $totalDebt += (float) RepairOrder::query()->where('client_id', $client->id)->where('payment_type', 'credit')->sum('total');
            $totalPaid = (float) CreditPayment::query()
                ->where('client_id', $client->id)
                ->sum('amount');
            $totalPaid += (float) RepairOrder::query()->where('client_id', $client->id)->where('payment_type', 'credit')->sum('advance_payment');
            $totalPaid += (float) RepairCreditPayment::query()->where('client_id', $client->id)
                ->whereHas('repairOrder', fn ($query) => $query->where('payment_type', 'credit'))->sum('amount');

            return array_merge($client->toArray(), $summary, [
                'total_debt' => $totalDebt,
                'total_paid' => $totalPaid,
            ]);
        })->filter(fn (array $row) => $row['balance'] > 0 || $row['credit_enabled']);
    }

    /**
     * @return array<string, float>
     */
    public function agingReport(): array
    {
        $today = now()->startOfDay();

        $clientIds = Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->distinct()
            ->pluck('client_id')
            ->merge(RepairOrder::query()
                ->where('payment_type', 'credit')
                ->whereIn('payment_status', ['pending', 'partial'])
                ->whereNotNull('client_id')
                ->distinct()
                ->pluck('client_id'))
            ->unique()
            ->values();

        $buckets = ['current' => 0, 'days_1_30' => 0, 'days_31_60' => 0, 'days_60_plus' => 0];

        foreach (Client::query()->whereKey($clientIds)->get() as $client) {
            foreach ($this->receivables($client) as $row) {
                if (! $row['due_date']) {
                    continue;
                }

                $daysOverdue = $today->diffInDays($row['due_date']->copy()->startOfDay(), false);
                $amount = $row['balance'];

                if ($daysOverdue >= 0) {
                    $buckets['current'] += $amount;
                } elseif ($daysOverdue >= -30) {
                    $buckets['days_1_30'] += $amount;
                } elseif ($daysOverdue >= -60) {
                    $buckets['days_31_60'] += $amount;
                } else {
                    $buckets['days_60_plus'] += $amount;
                }
            }
        }

        return array_map(fn ($v) => round($v, 2), $buckets);
    }

    /**
     * Reparaciones a crédito vencidas (todas las de la cartera), más antiguas primero.
     *
     * @return Collection<int, array{order: RepairOrder, balance: float, days_overdue: int}>
     */
    public function overdueRepairCredits(): Collection
    {
        $today = now()->startOfDay();

        return RepairOrder::query()
            ->where('payment_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->with('client')
            ->withSum('creditPayments', 'amount')
            ->orderBy('due_date')
            ->get()
            ->map(fn (RepairOrder $order): array => [
                'order' => $order,
                'balance' => $order->balance(),
                'days_overdue' => abs((int) $today->diffInDays($order->due_date->copy()->startOfDay(), false)),
            ])
            ->filter(fn (array $row): bool => $row['balance'] > 0.00001)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function portfolioSummary(): array
    {
        $pendingTotal = (float) Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->sum('total');

        $paymentsTotal = (float) CreditPayment::query()->sum('amount');

        $pendingRepairs = RepairOrder::query()->where('payment_type', 'credit')->whereIn('payment_status', ['pending', 'partial'])->withSum('creditPayments', 'amount')->get();
        $pendingTotal += (float) $pendingRepairs->sum('total');
        $paymentsTotal += (float) $pendingRepairs->sum('advance_payment') + (float) $pendingRepairs->sum('credit_payments_sum_amount');

        $repairsBalance = (float) $pendingRepairs->sum(fn (RepairOrder $order) => $order->balance());
        $repairsOverdue = (float) $pendingRepairs
            ->filter(fn (RepairOrder $order) => $order->due_date?->copy()->startOfDay()->isBefore(now()->startOfDay()))
            ->sum(fn (RepairOrder $order) => $order->balance());

        $overdueTotal = 0.0;
        $clientsWithPendingSales = Client::query()
            ->whereHas('sales', fn ($query) => $query
                ->where('payment_type', 'credit')
                ->where('status', 'pending'))
            ->get();

        foreach ($clientsWithPendingSales as $client) {
            foreach ($this->outstandingSales($client) as $row) {
                if ($row['sale']->due_date?->isPast()) {
                    $overdueTotal += $row['balance'];
                }
            }
        }

        $overdueTotal += $repairsOverdue;

        $clientsWithCredit = Client::where('credit_enabled', true)->count();
        $overLimitCount = Client::where('credit_enabled', true)
            ->where('credit_limit', '>', 0)
            ->get()
            ->filter(fn (Client $c) => $this->pendingDebt($c) > (float) $c->credit_limit)
            ->count();

        return [
            'pending_total' => round($pendingTotal, 2),
            'payments_total' => round($paymentsTotal, 2),
            'balance_total' => round(max(0, $pendingTotal - $paymentsTotal), 2),
            'overdue_total' => round($overdueTotal, 2),
            'repairs_balance' => round($repairsBalance, 2),
            'repairs_overdue' => round($repairsOverdue, 2),
            'repairs_count' => $pendingRepairs->filter(fn (RepairOrder $order) => $order->balance() > 0.00001)->count(),
            'clients_with_credit' => $clientsWithCredit,
            'over_limit_count' => $overLimitCount,
        ];
    }
}

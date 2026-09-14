<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CreditService
{
    /**
     * Calculate total mora (late-payment fee) owed by a client.
     * mora = sum of (sale.total * mora_rate% * overdue_days) per overdue sale,
     * capped at mora_max_pct% of the principal if configured.
     */
    public function moraForClient(Client $client): float
    {
        if (! $client->mora_enabled || (float) $client->mora_rate <= 0) {
            return 0;
        }

        $today = now()->startOfDay();
        $ratePerDay = (float) $client->mora_rate / 100;
        $graceDays = (int) ($client->mora_grace_days ?? 0);
        $maxPct = (float) ($client->mora_max_pct ?? 0);
        $totalMora = 0.0;

        foreach ($this->outstandingSales($client) as $row) {
            $sale = $row['sale'];
            if (! $sale->due_date || ! $sale->due_date->isBefore($today)) {
                continue;
            }

            $daysLate = (int) $today->diffInDays($sale->due_date->startOfDay(), false) * -1;
            $billableDays = max(0, $daysLate - $graceDays);
            if ($billableDays <= 0) {
                continue;
            }

            $principal = $row['balance'];
            $saleMora = $principal * $ratePerDay * $billableDays;

            if ($maxPct > 0) {
                $saleMora = min($saleMora, $principal * $maxPct / 100);
            }

            $totalMora += $saleMora;
        }

        return round($totalMora, 2);
    }

    /**
     * Returns per-sale mora details for a client.
     */
    public function moraSalesBreakdown(Client $client): array
    {
        if (! $client->mora_enabled || (float) $client->mora_rate <= 0) {
            return [];
        }

        $today = now()->startOfDay();
        $ratePerDay = (float) $client->mora_rate / 100;
        $graceDays = (int) ($client->mora_grace_days ?? 0);
        $maxPct = (float) ($client->mora_max_pct ?? 0);
        $breakdown = [];

        foreach ($this->outstandingSales($client) as $row) {
            $sale = $row['sale'];
            if (! $sale->due_date || ! $sale->due_date->isBefore($today)) {
                continue;
            }

            $daysLate = (int) $today->diffInDays($sale->due_date->startOfDay(), false) * -1;
            $billableDays = max(0, $daysLate - $graceDays);
            $principal = $row['balance'];
            $mora = $principal * $ratePerDay * $billableDays;

            if ($maxPct > 0) {
                $mora = min($mora, $principal * $maxPct / 100);
            }

            $breakdown[] = [
                'invoice_number' => $sale->invoice_number,
                'principal' => $principal,
                'days_late' => $daysLate,
                'billable_days' => $billableDays,
                'mora' => round($mora, 2),
            ];
        }

        return $breakdown;
    }

    public function pendingDebt(Client $client): float
    {
        return round($this->outstandingSales($client)->sum('balance'), 2);
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
        $balance = $this->pendingDebt($client);
        $limit = (float) $client->credit_limit;
        $available = $this->availableCredit($client);

        return [
            'balance' => $balance,
            'credit_limit' => $limit,
            'available_credit' => $client->credit_enabled ? ($limit > 0 ? $available : null) : 0,
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
            $totalPaid = (float) CreditPayment::query()
                ->where('client_id', $client->id)
                ->sum('amount');

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
            ->pluck('client_id');

        $buckets = ['current' => 0, 'days_1_30' => 0, 'days_31_60' => 0, 'days_60_plus' => 0];

        foreach (Client::query()->whereKey($clientIds)->get() as $client) {
            foreach ($this->outstandingSales($client) as $row) {
                $sale = $row['sale'];
                if (! $sale->due_date) {
                    continue;
                }

                $daysOverdue = $today->diffInDays($sale->due_date, false);
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
     * @return array<string, mixed>
     */
    public function portfolioSummary(): array
    {
        $pendingTotal = (float) Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->sum('total');

        $paymentsTotal = (float) CreditPayment::query()->sum('amount');

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
            'clients_with_credit' => $clientsWithCredit,
            'over_limit_count' => $overLimitCount,
        ];
    }
}

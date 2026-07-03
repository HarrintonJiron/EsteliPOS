<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CreditService
{
    public function pendingDebt(Client $client): float
    {
        $totalSales = (float) Sale::query()
            ->where('client_id', $client->id)
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->sum('total');

        $totalPaid = (float) CreditPayment::query()
            ->where('client_id', $client->id)
            ->sum('amount');

        return max(0, round($totalSales - $totalPaid, 2));
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

        $pending = Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->get(['id', 'client_id', 'total', 'due_date']);

        $buckets = ['current' => 0, 'days_1_30' => 0, 'days_31_60' => 0, 'days_60_plus' => 0];

        foreach ($pending as $sale) {
            $daysOverdue = $today->diffInDays($sale->due_date, false);
            $paid = (float) CreditPayment::where('client_id', $sale->client_id)->sum('amount');
            $amount = max(0, (float) $sale->total);

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

        $overdueTotal = (float) Sale::query()
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('total');

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

<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class LedgerService
{
    /**
     * Signed balance of an account as of (but not including) a given date, using its natural side.
     */
    public function openingBalance(Account $account, ?string $beforeDate = null): float
    {
        $query = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($beforeDate) {
                $q->where('status', 'posted');
                if ($beforeDate) {
                    $q->whereDate('date', '<', $beforeDate);
                }
            });

        $debit = (float) (clone $query)->sum('debit');
        $credit = (float) (clone $query)->sum('credit');

        return $account->nature === 'debit' ? $debit - $credit : $credit - $debit;
    }

    /**
     * Posted movements for an account within an optional date range, ordered chronologically.
     */
    public function movementsForAccount(Account $account, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return JournalEntryLine::with('journalEntry')
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            })
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry->date->format('Y-m-d') . '-' . str_pad($line->journalEntry->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }

    /**
     * Balance de comprobación: debe/haber acumulado por cuenta postable dentro del rango dado.
     */
    public function trialBalance(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $totals = JournalEntryLine::selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return Account::postable()->orderBy('code')->get()
            ->map(function (Account $account) use ($totals) {
                $row = $totals->get($account->id);
                $account->debe = round((float) ($row->debit ?? 0), 2);
                $account->haber = round((float) ($row->credit ?? 0), 2);
                $account->diferencia = round($account->debe - $account->haber, 2);

                return $account;
            })
            ->filter(fn (Account $account) => $account->debe > 0 || $account->haber > 0)
            ->values();
    }
}

<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PeriodClosingService
{
    public function closeMonth(FiscalPeriod $period, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($period, $user, $notes) {
            $period = FiscalPeriod::lockForUpdate()->findOrFail($period->id);
            $this->assertType($period, FiscalPeriod::TYPE_MONTHLY);

            if ($period->status === FiscalPeriod::STATUS_CLOSED) {
                throw new \RuntimeException('El período mensual ya se encuentra cerrado.');
            }
            if ($period->end_date->isFuture()) {
                throw new \RuntimeException('No se puede cerrar un período que todavía no ha finalizado.');
            }

            $this->assertNoDrafts($period);
            $this->assertPostedEntriesBalanced($period);
            $period->notes = $notes;
            $period->close($user);
            $this->audit('accounting.period.closed', "Cierre mensual {$period->month}/{$period->year}", $period, $user);
        });
    }

    public function reopenMonth(FiscalPeriod $period, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($period, $user, $notes) {
            $period = FiscalPeriod::lockForUpdate()->findOrFail($period->id);
            $this->assertType($period, FiscalPeriod::TYPE_MONTHLY);

            $annual = FiscalPeriod::annual()->where('year', $period->year)->first();
            if ($annual?->status === FiscalPeriod::STATUS_CLOSED) {
                throw new \RuntimeException('Debe reabrir primero el cierre anual antes de reabrir un mes.');
            }
            if ($period->status === FiscalPeriod::STATUS_OPEN) {
                throw new \RuntimeException('El período mensual ya se encuentra abierto.');
            }

            $period->notes = $notes;
            $period->reopen($user);
            $this->audit('accounting.period.reopened', "Reapertura mensual {$period->month}/{$period->year}", $period, $user);
        });
    }

    public function closeYear(FiscalPeriod $period, User $user, ?string $notes = null): ?JournalEntry
    {
        return DB::transaction(function () use ($period, $user, $notes) {
            $period = FiscalPeriod::lockForUpdate()->findOrFail($period->id);
            $this->assertType($period, FiscalPeriod::TYPE_ANNUAL);

            if ($period->status === FiscalPeriod::STATUS_CLOSED) {
                throw new \RuntimeException('El ejercicio anual ya se encuentra cerrado.');
            }

            $closedMonths = FiscalPeriod::monthly()->where('year', $period->year)->closed()->count();
            if ($closedMonths !== 12) {
                throw new \RuntimeException('Debe cerrar los 12 meses del año antes de realizar el cierre anual.');
            }

            $this->assertNoDrafts($period);
            $this->assertPostedEntriesBalanced($period);
            $entry = $this->createAnnualClosingEntry($period, $user);
            $period->notes = $notes;
            $period->close($user);
            $this->audit('accounting.year.closed', "Cierre anual {$period->year}", $period, $user, [
                'journal_entry_id' => $entry?->id,
            ]);

            return $entry;
        });
    }

    public function reopenYear(FiscalPeriod $period, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($period, $user, $notes) {
            $period = FiscalPeriod::lockForUpdate()->findOrFail($period->id);
            $this->assertType($period, FiscalPeriod::TYPE_ANNUAL);

            if ($period->status === FiscalPeriod::STATUS_OPEN) {
                throw new \RuntimeException('El ejercicio anual ya se encuentra abierto.');
            }

            $entry = $this->closingEntry($period);
            if ($entry?->status === JournalEntry::STATUS_POSTED) {
                $entry->update([
                    'status' => JournalEntry::STATUS_VOIDED,
                    'voided_at' => now(),
                    'notes' => trim(($entry->notes ? $entry->notes . ' | ' : '') . 'Anulado por reapertura del ejercicio.'),
                ]);
            }

            $period->notes = $notes;
            $period->reopen($user);
            $this->audit('accounting.year.reopened', "Reapertura anual {$period->year}", $period, $user, [
                'journal_entry_id' => $entry?->id,
            ]);
        });
    }

    public function closingEntry(FiscalPeriod $period): ?JournalEntry
    {
        return JournalEntry::where('source_type', FiscalPeriod::class)
            ->where('source_id', $period->id)
            ->latest('id')
            ->first();
    }

    public function pendingDrafts(FiscalPeriod $period): int
    {
        return JournalEntry::draft()->whereBetween('date', [$period->start_date, $period->end_date])->count();
    }

    private function createAnnualClosingEntry(FiscalPeriod $period, User $user): ?JournalEntry
    {
        if ($this->closingEntry($period)?->status === JournalEntry::STATUS_POSTED) {
            throw new \RuntimeException('El ejercicio ya tiene un asiento de cierre contabilizado.');
        }

        $types = collect(Account::MAIN_GROUPS)
            ->filter(fn (string $group) => in_array($group, ['ingresos', 'costos', 'gastos', 'otros_ingresos', 'otros_gastos'], true))
            ->keys();

        $balances = JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id, SUM(journal_entry_lines.debit) debit, SUM(journal_entry_lines.credit) credit')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('accounts.type', $types)
            ->whereHas('journalEntry', fn ($query) => $query->posted()->whereBetween('date', [$period->start_date, $period->end_date]))
            ->groupBy('journal_entry_lines.account_id')
            ->get();

        $lines = [];
        foreach ($balances as $balance) {
            $netDebit = round((float) $balance->debit - (float) $balance->credit, 2);
            if ($netDebit > 0) {
                $lines[] = ['account_id' => $balance->account_id, 'detail' => "Cierre {$period->year}", 'debit' => 0, 'credit' => $netDebit];
            } elseif ($netDebit < 0) {
                $lines[] = ['account_id' => $balance->account_id, 'detail' => "Cierre {$period->year}", 'debit' => abs($netDebit), 'credit' => 0];
            }
        }

        if ($lines === []) {
            return null;
        }

        $debit = round((float) collect($lines)->sum('debit'), 2);
        $credit = round((float) collect($lines)->sum('credit'), 2);
        $resultAccount = Account::where('code', '3.3')->where('is_postable', true)->firstOrFail();
        if ($debit > $credit) {
            $lines[] = ['account_id' => $resultAccount->id, 'detail' => "Resultado del ejercicio {$period->year}", 'debit' => 0, 'credit' => $debit - $credit];
        } elseif ($credit > $debit) {
            $lines[] = ['account_id' => $resultAccount->id, 'detail' => "Pérdida del ejercicio {$period->year}", 'debit' => $credit - $debit, 'credit' => 0];
        }

        $total = round((float) collect($lines)->sum('debit'), 2);
        $entry = JournalEntry::create([
            'number' => NumberSequence::getNext('asiento'),
            'date' => $period->end_date,
            'concept' => "Cierre contable anual {$period->year}",
            'reference' => "CIERRE-{$period->year}",
            'status' => JournalEntry::STATUS_POSTED,
            'total_debit' => $total,
            'total_credit' => $total,
            'source_type' => FiscalPeriod::class,
            'source_id' => $period->id,
            'user_id' => $user->id,
            'posted_at' => now(),
            'notes' => 'Asiento automático de cierre de cuentas nominales.',
        ]);
        $entry->lines()->createMany($lines);

        return $entry;
    }

    private function assertNoDrafts(FiscalPeriod $period): void
    {
        $drafts = $this->pendingDrafts($period);
        if ($drafts > 0) {
            throw new \RuntimeException("No se puede cerrar: existen {$drafts} asiento(s) en borrador dentro del período.");
        }
    }

    private function assertPostedEntriesBalanced(FiscalPeriod $period): void
    {
        $invalid = JournalEntry::posted()->whereBetween('date', [$period->start_date, $period->end_date])
            ->whereColumn('total_debit', '!=', 'total_credit')->exists();
        if ($invalid) {
            throw new \RuntimeException('No se puede cerrar: existen asientos contabilizados desbalanceados.');
        }
    }

    private function assertType(FiscalPeriod $period, string $type): void
    {
        if ($period->type !== $type) throw new \InvalidArgumentException('El tipo de período no corresponde a esta operación.');
    }

    private function audit(string $action, string $description, FiscalPeriod $period, User $user, array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => $user->id, 'action' => $action, 'description' => $description,
            'model_type' => FiscalPeriod::class, 'model_id' => $period->id,
            'new_values' => $newValues + ['status' => $period->status, 'notes' => $period->notes],
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
        ]);
    }
}

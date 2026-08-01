<?php

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\PeriodClosingService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);
    $this->user = User::factory()->create();
    $this->accounting = app(AccountingService::class);
    $this->closing = app(PeriodClosingService::class);
    $this->expense = Account::where('code', '6.1.02')->firstOrFail();
    $this->revenue = Account::where('code', '4.1')->firstOrFail();
    $this->cash = Account::where('code', '1.1.01')->firstOrFail();
});

test('a month with draft entries cannot be closed', function () {
    $this->accounting->createEntry([
        'date' => '1998-01-15', 'concept' => 'Borrador pendiente', 'user_id' => $this->user->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->cash->id, 'debit' => 0, 'credit' => 100],
        ],
    ]);

    expect(fn () => $this->closing->closeMonth(FiscalPeriod::forMonth(1998, 1), $this->user))
        ->toThrow(RuntimeException::class, 'borrador');
});

test('annual close transfers profit and reopening voids the closing entry', function () {
    $this->accounting->createEntry([
        'date' => '1998-01-20', 'concept' => 'Resultado del ejercicio', 'user_id' => $this->user->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'debit' => 300, 'credit' => 0],
            ['account_id' => $this->revenue->id, 'debit' => 0, 'credit' => 500],
            ['account_id' => $this->cash->id, 'debit' => 200, 'credit' => 0],
        ],
    ], post: true);

    foreach (range(1, 12) as $month) {
        $this->closing->closeMonth(FiscalPeriod::forMonth(1998, $month), $this->user);
    }

    $annual = FiscalPeriod::forYear(1998);
    $entry = $this->closing->closeYear($annual, $this->user);
    $result = $entry->lines()->where('account_id', Account::where('code', '3.3')->value('id'))->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and((float) $result->credit)->toBe(200.0)
        ->and($annual->fresh()->status)->toBe(FiscalPeriod::STATUS_CLOSED);

    $this->closing->reopenYear($annual->fresh(), $this->user, 'Corrección autorizada');

    expect($entry->fresh()->status)->toBe(JournalEntry::STATUS_VOIDED)
        ->and($annual->fresh()->status)->toBe(FiscalPeriod::STATUS_OPEN);
});

test('future periods cannot be closed', function () {
    $future = FiscalPeriod::forMonth(now()->addYear()->year, 12);

    expect(fn () => $this->closing->closeMonth($future, $this->user))
        ->toThrow(RuntimeException::class, 'todavía no ha finalizado');
});

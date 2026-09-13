<?php

use App\Models\Account;
use App\Models\Arqueo;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\OperationalExpense;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\AccountingService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminForOperationalExpenses(): User
{
    test()->seed(ConfigurationSeeder::class);
    test()->seed(AccountingSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);

    return $user;
}

test('it registers an operational expense and posts the accounting entry', function () {
    $admin = adminForOperationalExpenses();
    $session = CajaSession::create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->post(route('reparaciones.gastos.store'), [
        'caja_session_id' => $session->id,
        'description' => 'Compra de herramientas del taller',
        'amount' => 150.75,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'funding_source' => 'sales_cash',
        'notes' => 'Prueba automatizada',
        'status' => OperationalExpense::STATUS_REGISTERED,
    ]);

    $response->assertRedirect();

    $expense = OperationalExpense::firstOrFail();
    expect((float) $expense->amount)->toBe(150.75)
        ->and($expense->user_id)->toBe($admin->id)
        ->and($expense->caja_session_id)->toBe($session->id);

    $entry = JournalEntry::where('source_type', OperationalExpense::class)
        ->where('source_id', $expense->id)
        ->where('status', JournalEntry::STATUS_POSTED)
        ->first();

    expect($entry)->not->toBeNull()
        ->and((float) $entry->total_debit)->toBe(150.75)
        ->and((float) $entry->total_credit)->toBe(150.75);
});

test('cash operational expenses are deducted from arqueo system cash total', function () {
    $admin = adminForOperationalExpenses();
    $session = CajaSession::create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'status' => 'open',
    ]);

    $client = Client::create([
        'name' => 'Cliente Arqueo',
        'phone' => '88880000',
        'email' => 'arqueo@example.com',
        'address' => 'Esteli',
    ]);

    Sale::create([
        'invoice_number' => 'FAC-TEST-001',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => $client->name,
        'date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => 'completed',
        'tax_included' => false,
        'tax_rate' => 0,
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
    ]);

    OperationalExpense::create([
        'user_id' => $admin->id,
        'caja_session_id' => $session->id,
        'description' => 'Pago de transporte',
        'amount' => 25,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'funding_source' => 'sales_cash',
        'status' => OperationalExpense::STATUS_REGISTERED,
    ]);

    $response = $this->actingAs($admin)->post(route('arqueo.run'), [
        'date' => now()->toDateString(),
        'caja_session_id' => $session->id,
        'physical_counts' => [
            ['amount' => 20, 'qty' => 3],
            ['amount' => 10, 'qty' => 1],
            ['amount' => 5, 'qty' => 1],
        ],
    ]);

    $response->assertOk();

    $arqueo = Arqueo::latest('id')->firstOrFail();
    expect((float) $arqueo->cash_total)->toBe(75.0);
});

test('external cash expenses do not reduce the sales cash closing', function () {
    $admin = adminForOperationalExpenses();
    $session = CajaSession::create([
        'date' => now()->toDateString(), 'opened_at' => now(), 'opened_by' => $admin->id,
        'opening_amount' => 100, 'status' => 'open',
    ]);

    OperationalExpense::create([
        'user_id' => $admin->id, 'description' => 'Flete pagado por administración',
        'amount' => 40, 'expense_date' => now()->toDateString(), 'payment_method' => 'cash',
        'funding_source' => 'external', 'status' => OperationalExpense::STATUS_REGISTERED,
    ]);

    $this->actingAs($admin)->post(route('arqueo.run'), [
        'date' => now()->toDateString(), 'caja_session_id' => $session->id,
        'physical_counts' => [['amount' => 100, 'qty' => 1]],
    ])->assertOk();

    $arqueo = Arqueo::latest('id')->firstOrFail();
    expect((float) $arqueo->cash_total)->toBe(100.0)
        ->and((float) $arqueo->details['totals']['cash_expenses'])->toBe(0.0)
        ->and(strlen($arqueo->snapshot_hash))->toBe(64);

    $pdf = $this->actingAs($admin)->get(route('arqueo.pdf', $arqueo));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF');

    expect(fn () => $arqueo->update(['cash_total' => 0]))
        ->toThrow(LogicException::class);
});

test('it cancels an operational expense and voids its journal entry', function () {
    $admin = adminForOperationalExpenses();
    $session = CajaSession::create([
        'date' => now()->toDateString(),
        'opened_at' => now(),
        'opened_by' => $admin->id,
        'status' => 'open',
    ]);

    $expense = OperationalExpense::create([
        'user_id' => $admin->id,
        'caja_session_id' => $session->id,
        'description' => 'Compra de insumos',
        'amount' => 80,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'funding_source' => 'sales_cash',
        'status' => OperationalExpense::STATUS_REGISTERED,
        'account_id' => Account::where('code', '6.1.99')->value('id'),
    ]);

    app(AccountingService::class)->recordOperationalExpense($expense);

    $this->actingAs($admin)
        ->delete(route('reparaciones.gastos.destroy', $expense))
        ->assertRedirect(route('gastos.index'));

    expect($expense->fresh()->status)->toBe(OperationalExpense::STATUS_CANCELLED);

    $entry = JournalEntry::where('source_type', OperationalExpense::class)
        ->where('source_id', $expense->id)
        ->latest('id')
        ->firstOrFail();

    expect($entry->status)->toBe(JournalEntry::STATUS_VOIDED);
});

test('cash closing history remains paginated with operational volume', function () {
    $admin = adminForOperationalExpenses();
    $now = now();
    Arqueo::query()->insert(collect(range(1, 500))->map(fn (int $id) => [
        'date' => $now->toDateString(), 'user_id' => $admin->id, 'currency' => 'NIO',
        'closed_at' => $now->copy()->subSeconds($id), 'total_sales_count' => 1,
        'total_sales_amount' => 10, 'cash_total' => 10, 'credit_payments_total' => 0,
        'physical_total' => 10, 'difference' => 0, 'details' => json_encode(['schema_version' => 2]),
        'snapshot_hash' => hash('sha256', (string) $id), 'created_at' => $now, 'updated_at' => $now,
    ])->all());

    $this->actingAs($admin)->get(route('arqueo.history'))
        ->assertOk()->assertSee('Historial de cierres');

    expect(Arqueo::query()->count())->toBe(500);
});

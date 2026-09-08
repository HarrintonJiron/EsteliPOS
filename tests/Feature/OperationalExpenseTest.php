<?php

use App\Models\Arqueo;
use App\Models\CajaSession;
use App\Models\JournalEntry;
use App\Models\OperationalExpense;
use App\Models\Client;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
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
        'status' => OperationalExpense::STATUS_REGISTERED,
        'account_id' => \App\Models\Account::where('code', '6.1.99')->value('id'),
    ]);

    app(\App\Services\AccountingService::class)->recordOperationalExpense($expense);

    $this->actingAs($admin)
        ->delete(route('reparaciones.gastos.destroy', $expense))
        ->assertRedirect(route('reparaciones.gastos.index'));

    expect($expense->fresh()->status)->toBe(OperationalExpense::STATUS_CANCELLED);

    $entry = JournalEntry::where('source_type', OperationalExpense::class)
        ->where('source_id', $expense->id)
        ->latest('id')
        ->firstOrFail();

    expect($entry->status)->toBe(JournalEntry::STATUS_VOIDED);
});
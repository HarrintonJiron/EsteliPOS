<?php

use App\Models\Client;
use App\Models\PrintJob;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function printQueueAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function printableSale(User $user): Sale
{
    $client = Client::query()->create([
        'name' => 'Cliente impresión',
        'code' => 'PRINT-CLIENT',
        'phone' => '88887777',
        'status' => 'active',
    ]);

    return Sale::query()->create([
        'invoice_number' => 'FAC-CENTRAL-001',
        'client_id' => $client->id,
        'user_id' => $user->id,
        'billing_name' => 'Cliente impresión',
        'date' => now(),
        'payment_type' => 'cash',
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
        'status' => 'completed',
    ]);
}

test('local printing remains the default and opens the receipt directly', function () {
    $admin = printQueueAdmin();
    $sale = printableSale($admin);

    $this->actingAs($admin)
        ->post(route('printing.sales.enqueue', $sale))
        ->assertRedirect(route('facturacion.receipt', ['saleId' => $sale->id, 'autoprint' => 1]));

    expect(PrintJob::query()->count())->toBe(0);
});

test('central mode queues one copy and the station completes it', function () {
    $admin = printQueueAdmin();
    $sale = printableSale($admin);
    Setting::set('printing_mode', 'central', 'string', 'general');

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($admin)
            ->post(route('printing.sales.enqueue', $sale))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    expect(PrintJob::query()->count())->toBe(1);

    $response = $this->actingAs($admin)->postJson(route('printing.next'))
        ->assertOk()
        ->assertJsonPath('job.id', PrintJob::query()->value('id'));

    $job = PrintJob::query()->firstOrFail();
    expect($job->status)->toBe('processing')
        ->and($response->json('job.url'))->toContain('autoprint=1');

    $this->actingAs($admin)
        ->postJson(route('printing.complete', $job), ['success' => true])
        ->assertOk();

    expect($job->fresh()->status)->toBe('printed')
        ->and($job->fresh()->printed_at)->not->toBeNull()
        ->and($job->fresh()->dedup_key)->toBeNull();
});

test('another print station cannot complete a job it did not claim', function () {
    $station = printQueueAdmin();
    $otherUser = printQueueAdmin();
    $sale = printableSale($station);
    Setting::set('printing_mode', 'central', 'string', 'general');

    $this->actingAs($station)->post(route('printing.sales.enqueue', $sale));
    $this->actingAs($station)->postJson(route('printing.next'))->assertOk();
    $job = PrintJob::query()->firstOrFail();

    $this->actingAs($otherUser)
        ->postJson(route('printing.complete', $job), ['success' => true])
        ->assertStatus(409);

    expect($job->fresh()->status)->toBe('processing');
});

test('company settings can enable the central print queue', function () {
    $admin = printQueueAdmin();

    $this->actingAs($admin)
        ->get(route('settings.general'))
        ->assertOk()
        ->assertSee('Cola central')
        ->assertSee(route('printing.station'));
});

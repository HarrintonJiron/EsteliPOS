<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sales report includes transactions from the complete end date', function () {
    Carbon::setTestNow('2026-09-21 16:30:00');

    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);
    $client = Client::create([
        'name' => 'Cliente de reportes',
        'phone' => '0000-0000',
    ]);

    Sale::create([
        'invoice_number' => 'FAC-END-DATE',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => 'Cliente del día final',
        'date' => '2026-09-21 23:45:00',
        'subtotal' => 100,
        'tax_total' => 15,
        'total' => 115,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);

    Sale::create([
        'invoice_number' => 'FAC-NEXT-DAY',
        'client_id' => $client->id,
        'user_id' => $admin->id,
        'billing_name' => 'Cliente del día siguiente',
        'date' => '2026-09-22 00:00:00',
        'subtotal' => 200,
        'tax_total' => 30,
        'total' => 230,
        'payment_type' => 'cash',
        'status' => 'completed',
    ]);

    $this->actingAs($admin)
        ->get(route('reportes.index', [
            'report_type' => 'sales',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-21',
        ]))
        ->assertOk()
        ->assertSee('FAC-END-DATE')
        ->assertSee('1 transacciones')
        ->assertSee('class="report-print-area"', false)
        ->assertSee('class="report-print-header hidden"', false)
        ->assertSee('Información del reporte')
        ->assertDontSee('FAC-NEXT-DAY');

    Carbon::setTestNow();
});

test('every commercial report exports a dedicated PDF document', function () {
    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true],
    );
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->sync([$role->id]);

    foreach (['sales', 'purchases', 'inventory', 'kardex', 'profit', 'abc', 'aging', 'slow', 'top_clients', 'sellers', 'categories'] as $type) {
        $response = $this->actingAs($admin)->get(route('reportes.pdf', [
            'report_type' => $type,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect($response->getContent())->toStartWith('%PDF-');
    }
});

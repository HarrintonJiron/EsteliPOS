<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function weeklyReportAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    return $user;
}

test('the weekly repair report only lists orders delivered within the selected week', function () {
    $admin = weeklyReportAdmin();
    $monday = now()->startOfWeek(Carbon\Carbon::MONDAY);

    $delivered = RepairOrder::create([
        'order_number' => 'REP-SEM-DELIVERED',
        'client_name' => 'Cliente entregado',
        'device_brand' => 'Samsung', 'device_model' => 'A10',
        'problem_description' => 'Pantalla',
        'status' => 'delivered', 'priority' => 'normal', 'user_id' => $admin->id,
        'received_date' => $monday, 'delivered_date' => $monday->copy()->addDays(2),
        'labor_cost' => 80, 'parts_cost' => 0, 'total' => 80, 'advance_payment' => 80,
        'payment_type' => 'cash', 'payment_status' => 'paid',
    ]);

    RepairOrder::create([
        'order_number' => 'REP-SEM-PENDING',
        'client_name' => 'Cliente sin entregar',
        'device_brand' => 'Motorola', 'device_model' => 'G54',
        'problem_description' => 'No enciende',
        'status' => 'received', 'priority' => 'normal', 'user_id' => $admin->id,
        'received_date' => $monday->copy()->addDays(5),
        'labor_cost' => 0, 'parts_cost' => 0, 'total' => 50, 'advance_payment' => 0,
        'payment_type' => 'cash', 'payment_status' => 'pending',
    ]);

    RepairOrder::create([
        'order_number' => 'REP-SEM-OTHER-WEEK',
        'client_name' => 'Cliente otra semana',
        'device_brand' => 'Apple', 'device_model' => 'iPhone 8',
        'problem_description' => 'Batería',
        'status' => 'delivered', 'priority' => 'normal', 'user_id' => $admin->id,
        'received_date' => $monday->copy()->subWeek(), 'delivered_date' => $monday->copy()->subDays(3),
        'labor_cost' => 40, 'parts_cost' => 0, 'total' => 40, 'advance_payment' => 40,
        'payment_type' => 'cash', 'payment_status' => 'paid',
    ]);

    $response = $this->actingAs($admin)->get(route('reparaciones.informe-semanal'));

    $response->assertOk();
    $orders = $response->viewData('orders');
    $summary = $response->viewData('summary');

    expect($orders->pluck('order_number')->all())->toBe([$delivered->order_number])
        ->and($summary['count'])->toBe(1)
        ->and($summary['total'])->toBe(80.0)
        ->and($response->viewData('pendingInWindow'))->toBe(1);
});

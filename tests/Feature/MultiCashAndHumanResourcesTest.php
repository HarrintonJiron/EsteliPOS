<?php

use App\Models\AttendanceRecord;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function operationsAdmin(string $email): User
{
    test()->seed(ConfigurationSeeder::class);
    $user = User::factory()->create(['email' => $email, 'role' => 'admin', 'is_active' => true]);
    $role = Role::query()->where('slug', 'admin')->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    return $user;
}

test('two cashiers can open independent cash sessions and only see their movements', function () {
    $first = operationsAdmin('cashier-one@example.com');
    $second = operationsAdmin('cashier-two@example.com');

    $this->actingAs($first)->post(route('arqueo.open'), ['opening_amount' => 100])->assertSessionHasNoErrors();
    $this->actingAs($second)->post(route('arqueo.open'), ['opening_amount' => 300])->assertSessionHasNoErrors();

    $sessions = CajaSession::query()->where('status', 'open')->get()->keyBy('opened_by');
    expect($sessions)->toHaveCount(2);
    $client = Client::query()->create(['code' => 'GEN-MULTI', 'name' => 'Cliente caja', 'phone' => 'N/A']);

    Sale::query()->create([
        'invoice_number' => 'MULTI-001', 'client_id' => $client->id, 'user_id' => $first->id, 'caja_session_id' => $sessions[$first->id]->id,
        'date' => now(), 'subtotal' => 50, 'tax_total' => 0, 'total' => 50, 'payment_type' => 'cash', 'status' => 'completed',
    ]);

    $this->actingAs($first)->get(route('arqueo.index'))->assertOk()->assertSee('C$ 150.00');
    $this->actingAs($second)->get(route('arqueo.index'))->assertOk()->assertSee('C$ 300.00')->assertDontSee('C$ 150.00');
});

test('attendance and performance evaluations persist real employee records', function () {
    $admin = operationsAdmin('hr-admin@example.com');
    $employee = Employee::query()->create([
        'name' => 'María López', 'cedula' => '001-010190-0001A', 'position' => 'Cajera',
        'salary' => 12000, 'hire_date' => now()->subYear(), 'contract_type' => 'full_time',
        'payment_frequency' => 'monthly', 'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('rrhh.attendance.store'), [
        'employee_id' => $employee->id, 'work_date' => now()->toDateString(), 'status' => 'late',
        'check_in' => '08:12', 'check_out' => '17:00', 'notes' => 'Tráfico',
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->post(route('rrhh.evaluations.store'), [
        'employee_id' => $employee->id, 'evaluation_date' => now()->toDateString(), 'period' => '2026-II',
        'score' => 92, 'strengths' => 'Servicio al cliente', 'improvements' => 'Conteo de caja',
    ])->assertSessionHasNoErrors();

    expect(AttendanceRecord::query()->firstOrFail()->status)->toBe('late')
        ->and(PerformanceEvaluation::query()->firstOrFail()->score)->toBe(92);

    $this->actingAs($admin)->get(route('rrhh.evaluations'))->assertOk()->assertSee('María López')->assertSee('Excelente');
});

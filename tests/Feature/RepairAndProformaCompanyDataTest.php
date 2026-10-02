<?php

use App\Models\Proforma;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function companyDataAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);

    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

    return $user;
}

function configureCompany(): void
{
    foreach ([
        'company_name' => 'Celulares Estelí Center',
        'company_legal_name' => 'Estelí Center S.A.',
        'company_ruc' => 'J0310000999999',
        'company_phone' => '2713-4455',
        'company_email' => 'ventas@estelicenter.test',
        'company_address' => 'Barrio Rosario, frente al parque',
        'company_city' => 'Estelí',
        'company_country' => 'Nicaragua',
        'date_format' => 'Y-m-d',
    ] as $key => $value) {
        Setting::set($key, $value, 'string', 'general');
    }
}

function repairOrderForCompanyTests(User $admin): RepairOrder
{
    return RepairOrder::create([
        'order_number' => 'REP-000777',
        'client_name' => 'Cliente Datos Empresa',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'problem_description' => 'No enciende',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => '2026-09-05',
        'user_id' => $admin->id,
        'labor_cost' => 10,
        'total' => 10,
    ]);
}

test('the repair pdf shows the company data configured in the system, not fixed text', function () {
    $admin = companyDataAdmin();
    configureCompany();
    $order = repairOrderForCompanyTests($admin);

    $this->actingAs($admin)
        ->get(route('reparaciones.pdf', $order))
        ->assertOk()
        ->assertSeeText('Celulares Estelí Center')
        ->assertSeeText('Estelí Center S.A.')
        ->assertSeeText('RUC: J0310000999999')
        ->assertSeeText('Tel: 2713-4455')
        ->assertSeeText('ventas@estelicenter.test')
        ->assertSeeText('Barrio Rosario, frente al parque')
        ->assertSeeText('Recibido: 2026-09-05')
        ->assertSeeText('Celulares Estelí Center no se hace responsable')
        ->assertDontSeeText('AGROSERVICIO')
        ->assertDontSeeText('J10240330417')
        ->assertDontSeeText('2772-0000')
        ->assertDontSeeText('Managua');
});

test('the repair pdf follows a later change of the company name', function () {
    $admin = companyDataAdmin();
    configureCompany();
    $order = repairOrderForCompanyTests($admin);

    Setting::set('company_name', 'Nuevo Nombre Comercial', 'string', 'general');

    $this->actingAs($admin)
        ->get(route('reparaciones.pdf', $order))
        ->assertOk()
        ->assertSeeText('Nuevo Nombre Comercial')
        ->assertDontSeeText('Celulares Estelí Center no se hace');
});

test('the repair pdf leaves out company lines that were not configured', function () {
    $admin = companyDataAdmin();
    Setting::set('company_name', 'Solo Nombre', 'string', 'general');
    Setting::set('company_ruc', '', 'string', 'general');
    Setting::set('company_phone', '', 'string', 'general');
    Setting::set('company_email', '', 'string', 'general');
    Setting::set('company_address', '', 'string', 'general');
    $order = repairOrderForCompanyTests($admin);

    $this->actingAs($admin)
        ->get(route('reparaciones.pdf', $order))
        ->assertOk()
        ->assertSeeText('Solo Nombre')
        ->assertDontSeeText('RUC:')
        ->assertDontSeeText('Tel:');
});

test('the proforma pdf shows the company data configured in the system', function () {
    $admin = companyDataAdmin();
    configureCompany();

    $proforma = Proforma::create([
        'proforma_number' => 'PRO-000777',
        'user_id' => $admin->id,
        'client_name' => 'Cliente Proforma',
        'date' => '2026-09-05',
        'expiry_date' => '2026-09-20',
        'tax_rate' => 0.15,
        'tax_included' => false,
        'status' => 'draft',
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
    ]);

    $this->actingAs($admin)
        ->get(route('proformas.pdf', $proforma->id))
        ->assertOk()
        ->assertSeeText('Celulares Estelí Center')
        ->assertSeeText('J0310000999999')
        ->assertSeeText('2713-4455')
        ->assertSeeText('Barrio Rosario, frente al parque')
        ->assertSeeText('Fecha: 2026-09-05')
        ->assertDontSeeText('AGROSERVICIO')
        ->assertDontSeeText('J10240330417')
        ->assertDontSeeText('Managua');
});

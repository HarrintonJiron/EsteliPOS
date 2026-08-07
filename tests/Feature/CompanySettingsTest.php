<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validCompanySettings(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'EsteliPOS Comercial',
        'company_legal_name' => 'EsteliPOS, S.A.',
        'company_ruc' => 'J0310000000012',
        'company_phone' => '+505 2713-0000',
        'company_email' => 'administracion@estelipos.test',
        'company_address' => 'De la catedral 2 cuadras al norte',
        'company_city' => 'Estelí',
        'company_country' => 'Nicaragua',
        'currency' => 'NIO',
        'currency_symbol' => 'C$',
        'timezone' => 'America/Managua',
        'date_format' => 'd/m/Y',
        'language' => 'es',
        'invoice_footer' => 'Conserve este documento para cualquier reclamo.',
        'receipt_message' => 'Gracias por preferirnos.',
        'repair_warranty_text' => 'Garantía de 60 días por mano de obra en taller.',
        'system_name' => 'EsteliPOS',
    ], $overrides);
}

function companyAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->attach($role);

    return $admin;
}

test('administrator can view the complete company settings form', function () {
    $admin = companyAdmin();

    $this->actingAs($admin)->get(route('settings.general'))
        ->assertOk()
        ->assertSee('Razón social')
        ->assertSee('Logo para tickets')
        ->assertSee('Pie de factura')
        ->assertSee('Garantía predeterminada — reparaciones')
        ->assertSee('data-dirty-form', false);
});

test('client branding uses a prominent logo and identifies the system developer', function () {
    Storage::fake('public');
    Storage::disk('public')->put('company/client-logo.png', 'test-logo');
    $admin = companyAdmin();
    Setting::set('company_logo', 'company/client-logo.png', 'string', 'general');

    $this->actingAs($admin)->get(route('settings.general'))
        ->assertOk()
        ->assertSee('data-company-logo', false)
        ->assertSee('h-20', false);

    auth()->logout();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-developer-credit', false)
        ->assertSee('Northlink Microsystem');
});

test('company settings are validated persisted and audited', function () {
    $admin = companyAdmin();

    $this->actingAs($admin)
        ->post(route('settings.general.update'), validCompanySettings())
        ->assertRedirect(route('settings.general'))
        ->assertSessionHas('success');

    expect(Setting::get('company_name'))->toBe('EsteliPOS Comercial')
        ->and(Setting::get('company_legal_name'))->toBe('EsteliPOS, S.A.')
        ->and(Setting::get('currency'))->toBe('NIO')
        ->and(Setting::get('currency_symbol'))->toBe('C$')
        ->and(Setting::get('system_name'))->toBe('EsteliPOS')
        ->and(Setting::get('repair_warranty_text'))->toBe('Garantía de 60 días por mano de obra en taller.');

    $log = AuditLog::where('action', 'settings.company.updated')->firstOrFail();
    expect($log->user_id)->toBe($admin->id)
        ->and($log->new_values['company_name'])->toBe('EsteliPOS Comercial')
        ->and($log->new_values['system_name'])->toBe('EsteliPOS');
});

test('company and ticket logos are stored on the public disk', function () {
    Storage::fake('public');
    $admin = companyAdmin();

    $payload = validCompanySettings([
        'company_logo' => UploadedFile::fake()->image('empresa.png', 600, 300),
        'ticket_logo' => UploadedFile::fake()->image('ticket.webp', 300, 150),
    ]);

    $this->actingAs($admin)->post(route('settings.general.update'), $payload)
        ->assertRedirect(route('settings.general'));

    $companyLogo = Setting::get('company_logo');
    $ticketLogo = Setting::get('ticket_logo');

    expect($companyLogo)->toStartWith('company/')
        ->and($ticketLogo)->toStartWith('company/');
    Storage::disk('public')->assertExists([$companyLogo, $ticketLogo]);
});

test('oversized company logos are resized and optimized automatically', function () {
    Storage::fake('public');
    $admin = companyAdmin();

    $payload = validCompanySettings([
        'company_logo' => UploadedFile::fake()->image('big-logo.png', 4000, 3000),
    ]);

    $this->actingAs($admin)->post(route('settings.general.update'), $payload)
        ->assertRedirect(route('settings.general'))
        ->assertSessionHasNoErrors();

    $companyLogo = Setting::get('company_logo');
    Storage::disk('public')->assertExists($companyLogo);

    $size = getimagesize(Storage::disk('public')->path($companyLogo));

    expect($size)->not->toBeFalse()
        ->and($size[0])->toBeLessThanOrEqual(1200)
        ->and($size[1])->toBeLessThanOrEqual(1200);
});

test('invalid company data and unsafe logo formats are rejected', function () {
    Storage::fake('public');
    $admin = companyAdmin();

    $payload = validCompanySettings([
        'company_email' => 'correo-invalido',
        'currency' => 'BTC',
        'company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    ]);

    $this->actingAs($admin)->post(route('settings.general.update'), $payload)
        ->assertSessionHasErrors(['company_email', 'currency', 'company_logo']);

    expect(AuditLog::where('action', 'settings.company.updated')->exists())->toBeFalse();
});

test('system timezone and language are applied on subsequent web requests', function () {
    $admin = companyAdmin();

    $this->actingAs($admin)->post(route('settings.general.update'), validCompanySettings([
        'timezone' => 'America/Costa_Rica',
        'language' => 'en',
    ]));

    $this->get(route('settings.general'))->assertOk();

    expect(config('app.timezone'))->toBe('America/Costa_Rica')
        ->and(app()->getLocale())->toBe('en');
});

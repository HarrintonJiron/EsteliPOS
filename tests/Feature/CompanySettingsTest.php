<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImageProcessingService;
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
        ->assertSee('data-application-brand', false)
        ->assertSee('EsteliPOS')
        ->assertSee('sidebar-brand-logo', false)
        ->assertSee('rounded-full', false);

    auth()->logout();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-developer-credit', false)
        ->assertSee('Northlink Microsystem')
        ->assertSee('login-hero-nicaragua-v2.png', false)
        ->assertSee('northlink-logo-login.png', false)
        ->assertSee('login-hero-product', false)
        ->assertSee('Sistema de punto de venta')
        ->assertSee('Contactar a Northlink');

    $this->actingAs($admin)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Soporte oficial')
        ->assertSee('EsteliPOS')
        ->assertSee('Desarrollado por Northlink Microsystem')
        ->assertSee('Northlink Microsystem')
        ->assertSee('northlinkni.com');

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
    $this->get('/media/'.$companyLogo)
        ->assertSuccessful()
        ->assertHeader('Content-Type', str_ends_with($companyLogo, '.webp') ? 'image/webp' : 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('the preventive image pipeline command verifies storage without leaving files', function () {
    Storage::fake('public');

    $this->artisan('app:verify-image-pipeline')
        ->expectsOutputToContain('[OK] El manejo de logos e imágenes está operativo.')
        ->assertSuccessful();

    expect(Storage::disk('public')->allFiles('company/.health-check'))->toBeEmpty();
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

test('a logo processing failure returns to settings with a visible error instead of a blank response', function () {
    Storage::fake('public');
    $admin = companyAdmin();
    $processor = Mockery::mock(ImageProcessingService::class);
    $processor->shouldReceive('storePublicImage')
        ->once()
        ->andThrow(new RuntimeException('Fallo controlado del procesador'));
    app()->instance(ImageProcessingService::class, $processor);

    $response = $this->actingAs($admin)->post(route('settings.general.update'), validCompanySettings([
        'company_logo' => UploadedFile::fake()->image('empresa.png', 600, 300),
    ]));

    $response->assertRedirect()
        ->assertSessionHasErrors(['company_logo']);

    expect(Setting::get('company_logo'))->toBeNull()
        ->and(AuditLog::where('action', 'settings.company.updated')->exists())->toBeFalse();
});

test('logos above the safe upload size are rejected without changing company settings', function () {
    Storage::fake('public');
    $admin = companyAdmin();

    $this->actingAs($admin)->post(route('settings.general.update'), validCompanySettings([
        'ticket_logo' => UploadedFile::fake()->create('ticket.png', 8193, 'image/png'),
    ]))->assertSessionHasErrors(['ticket_logo']);

    expect(Setting::get('ticket_logo'))->toBeNull();
});

test('logos with unsafe pixel dimensions are rejected before gd decodes them', function () {
    Storage::fake('public');
    $admin = companyAdmin();

    $this->actingAs($admin)->post(route('settings.general.update'), validCompanySettings([
        'company_logo' => UploadedFile::fake()->image('demasiado-grande.png', 5000, 5000),
    ]))->assertSessionHasErrors(['company_logo']);

    expect(Setting::get('company_logo'))->toBeNull()
        ->and(Storage::disk('public')->allFiles('company'))->toBeEmpty();
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

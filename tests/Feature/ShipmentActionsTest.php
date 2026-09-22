<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shipmentActionsAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

function shipmentActionsLimitedUser(array $permissions): User
{
    $user = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'Rol envíos '.uniqid(), 'slug' => 'envios-'.uniqid(), 'is_system' => false]);
    $user->roles()->attach($role);

    foreach ($permissions as $slug) {
        $permission = Permission::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'module' => str($slug)->before('.')->toString(),
            'action' => str($slug)->after('.')->toString(),
        ]);
        $role->permissions()->attach($permission);

        // Envíos vive dentro del módulo «Apartados y envíos».
        Module::query()->where('slug', 'operaciones_clientes')->first()?->roles()->syncWithoutDetaching([$role->id]);
    }

    return $user;
}

function actionsShipment(User $user, array $overrides = []): Shipment
{
    static $n = 0;
    $n++;

    return Shipment::create($overrides + [
        'number' => 'ENV-ACT-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
        'user_id' => $user->id,
        'recipient_name' => 'Destinatario '.$n,
        'department' => 'Estelí',
        'address' => 'Barrio Central',
        'status' => 'pending',
    ]);
}

test('the shipment list has view, edit and label actions and a quick status selector', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin);

    $this->actingAs($admin)->get(route('envios.index'))
        ->assertOk()
        ->assertSee('href="'.route('envios.show', $shipment).'"', false)
        ->assertSee('href="'.route('envios.edit', $shipment).'"', false)
        ->assertSee(route('envios.label', $shipment), false)
        ->assertSeeText('Editar')
        ->assertSee('action="'.route('envios.status', $shipment).'"', false)
        ->assertSeeText('Pendiente');
});

test('a user who can only view shipments does not get edit actions', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin);
    $viewer = shipmentActionsLimitedUser(['envios.view']);

    $this->actingAs($viewer)->get(route('envios.index'))
        ->assertOk()
        ->assertSee('href="'.route('envios.show', $shipment).'"', false)
        ->assertDontSee('href="'.route('envios.edit', $shipment).'"', false)
        ->assertDontSee(route('envios.status', $shipment), false)
        ->assertSeeText('Pendiente');

    $this->actingAs($viewer)->get(route('envios.show', $shipment))
        ->assertOk()
        ->assertDontSee(route('envios.edit', $shipment), false);

    $this->actingAs($viewer)->patch(route('envios.status', $shipment), ['status' => 'shipped'])->assertRedirect();
    expect($shipment->fresh()->status)->toBe('pending');
});

test('the quick status change updates the shipment and stamps the dates once', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin);

    $this->actingAs($admin)->patch(route('envios.status', $shipment), ['status' => 'shipped'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'Enviado'));

    $shipment->refresh();
    expect($shipment->status)->toBe('shipped')->and($shipment->shipped_at)->not->toBeNull()->and($shipment->delivered_at)->toBeNull();
    $shippedAt = $shipment->shipped_at;

    $this->travel(2)->hours();
    $this->actingAs($admin)->patch(route('envios.status', $shipment), ['status' => 'delivered'])->assertSessionHasNoErrors();

    $shipment->refresh();
    expect($shipment->status)->toBe('delivered')
        ->and($shipment->delivered_at)->not->toBeNull()
        ->and($shipment->shipped_at->equalTo($shippedAt))->toBeTrue();
});

test('an unknown shipment status is rejected', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin);

    $this->actingAs($admin)->patch(route('envios.status', $shipment), ['status' => 'perdido'])->assertSessionHasErrors('status');
    expect($shipment->fresh()->status)->toBe('pending');
});

test('the shipment page shows the status in spanish with a status form and an edit button', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin, ['status' => 'prepared', 'notes' => 'Llamar antes de entregar', 'is_fragile' => true]);

    $this->actingAs($admin)->get(route('envios.show', $shipment))
        ->assertOk()
        ->assertSeeText('Preparado')
        ->assertSeeText('Actualizar estado')
        ->assertSeeText('Editar envío')
        ->assertSee(route('envios.edit', $shipment), false)
        ->assertSeeText('Llamar antes de entregar')
        ->assertSeeText('Frágil');
});

test('editing a shipment from its edit page changes the status and the other data', function () {
    $admin = shipmentActionsAdmin();
    $shipment = actionsShipment($admin);

    $this->actingAs($admin)->get(route('envios.edit', $shipment))->assertOk()->assertSee('name="status"', false);

    $this->actingAs($admin)->put(route('envios.update', $shipment), [
        'recipient_name' => 'Nuevo Nombre',
        'recipient_phone' => '88887777',
        'department' => 'León',
        'municipality' => 'Nagarote',
        'address' => 'Nueva dirección 123',
        'carrier' => 'Cargotrans',
        'tracking_number' => 'GUIA-1',
        'shipping_cost' => 15.5,
        'status' => 'shipped',
        'notes' => 'Salió en el bus de las 6',
    ])->assertRedirect(route('envios.show', $shipment));

    $shipment->refresh();
    expect($shipment->recipient_name)->toBe('Nuevo Nombre')
        ->and($shipment->department)->toBe('León')
        ->and($shipment->carrier)->toBe('Cargotrans')
        ->and($shipment->status)->toBe('shipped')
        ->and($shipment->shipped_at)->not->toBeNull();
});

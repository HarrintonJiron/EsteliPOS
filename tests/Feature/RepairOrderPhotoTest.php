<?php

use App\Models\RepairOrder;
use App\Models\RepairOrderPhoto;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function repairPhotoAdmin(): User
{
    test()->seed(ConfigurationSeeder::class);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->attach(Role::where('slug', 'admin')->value('id'));
    openCashSessionFor($user);

    return $user;
}

function repairPhotoPayload(): array
{
    return [
        'client_name' => 'Cliente Foto QA',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'problem_description' => 'Pantalla quebrada',
        'status' => 'received',
        'priority' => 'normal',
        'received_date' => now()->toDateString(),
        'payment_type' => 'cash',
    ];
}

/** @return array<int, UploadedFile> */
function fakeRepairPhotos(int $count, string $prefix = 'equipo'): array
{
    return collect(range(1, $count))
        ->map(fn (int $i) => UploadedFile::fake()->image("{$prefix}-{$i}.jpg", 1200, 900))
        ->all();
}

function repairOrderWithPhotos(User $admin, int $count): RepairOrder
{
    test()->actingAs($admin)->post(route('reparaciones.store'), [
        ...repairPhotoPayload(),
        'device_photos' => fakeRepairPhotos($count),
    ])->assertSessionHasNoErrors();

    return RepairOrder::query()->latest('id')->firstOrFail();
}

test('repair forms expose one discount value and an additive photo control', function () {
    $admin = repairPhotoAdmin();

    $create = $this->actingAs($admin)->get(route('reparaciones.create'));
    $create->assertOk()
        ->assertSee('name="discount_type"', false)
        ->assertSee('id="discountValueInput"', false)
        ->assertSee('type="hidden" name="discount_percentage"', false)
        ->assertSee('type="hidden" name="discount_amount"', false)
        ->assertSee('data-add-photo', false)
        ->assertSee('+ Agregar otra foto');
});

test('several device photos can be uploaded with a repair order and are shown on the detail page', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();

    $order = repairOrderWithPhotos($admin, 3);

    expect($order->photos)->toHaveCount(3);

    foreach ($order->photos as $photo) {
        expect($photo->photo_path)->toStartWith('repair-orders/')
            ->and($photo->url)->toBe('/reparaciones/'.$order->id.'/fotos/'.$photo->id);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    $show = $this->actingAs($admin)->get(route('reparaciones.show', $order))->assertOk();
    foreach ($order->photos as $photo) {
        $show->assertSee($photo->url, false);
    }
    $show->assertSee('Fotos del equipo')->assertSee('3 de 5');
});

test('repair photos are only served to signed in users', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();
    $order = repairOrderWithPhotos($admin, 1);
    $photo = $order->photos->first();

    $this->app['auth']->logout();
    $this->get($photo->url)->assertRedirect(route('login'));

    $this->actingAs($admin)->get($photo->url)->assertOk();

    $otherOrder = repairOrderWithPhotos($admin, 1);
    $this->actingAs($admin)
        ->get(route('reparaciones.photos.show', [$otherOrder->id, $photo->id]))
        ->assertNotFound();
});

test('a repair order accepts at most five photos', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        ...repairPhotoPayload(),
        'device_photos' => fakeRepairPhotos(6),
    ])->assertSessionHasErrors('device_photos');

    expect(RepairOrder::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles('repair-orders'))->toBe([]);

    $order = repairOrderWithPhotos($admin, 5);
    expect($order->photos)->toHaveCount(5);
});

test('a repair order rejects a non image attachment', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        ...repairPhotoPayload(),
        'device_photos' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
    ])->assertSessionHasErrors('device_photos.0');

    expect(RepairOrder::query()->count())->toBe(0);
});

test('editing an order can add photos, remove some and never exceed five', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();
    $order = repairOrderWithPhotos($admin, 4);
    $paths = $order->photos->pluck('photo_path', 'id');

    $this->actingAs($admin)->get(route('reparaciones.edit', $order))
        ->assertOk()
        ->assertSee($order->photos->first()->url, false)
        ->assertDontSee('href="repair-orders/', false);

    // 4 existentes + 2 nuevas = 6: se rechaza y no se guarda nada
    $this->actingAs($admin)->put(route('reparaciones.update', $order), [
        ...repairPhotoPayload(),
        'device_photos' => fakeRepairPhotos(2, 'nueva'),
    ])->assertSessionHasErrors('device_photos');

    expect($order->photos()->count())->toBe(4)
        ->and(Storage::disk('public')->allFiles('repair-orders'))->toHaveCount(4);

    // quitando dos, entran dos nuevas
    $removed = $order->photos->take(2);
    $this->actingAs($admin)->put(route('reparaciones.update', $order), [
        ...repairPhotoPayload(),
        'remove_photo_ids' => $removed->pluck('id')->all(),
        'device_photos' => fakeRepairPhotos(2, 'nueva'),
    ])->assertRedirect(route('reparaciones.show', $order));

    expect($order->photos()->count())->toBe(4);
    foreach ($removed as $photo) {
        expect(RepairOrderPhoto::find($photo->id))->toBeNull();
        Storage::disk('public')->assertMissing($paths[$photo->id]);
    }
    expect(Storage::disk('public')->allFiles('repair-orders'))->toHaveCount(4);
});

test('deleting a repair order also deletes its photos', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();
    $order = repairOrderWithPhotos($admin, 2);
    $paths = $order->photos->pluck('photo_path');

    $this->actingAs($admin)
        ->delete(route('reparaciones.destroy', $order))
        ->assertRedirect(route('reparaciones.index'));

    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
});

test('a failed update returns to the form and keeps the current photos', function () {
    Storage::fake('public');
    $admin = repairPhotoAdmin();

    $this->actingAs($admin)->post(route('reparaciones.store'), [
        ...repairPhotoPayload(),
        'labor_cost' => 100,
        'advance_payment' => 100,
        'device_photos' => fakeRepairPhotos(1),
    ])->assertRedirect();

    $order = RepairOrder::query()->firstOrFail();
    $originalPath = $order->photos->first()->photo_path;

    $this->actingAs($admin)
        ->from(route('reparaciones.edit', $order))
        ->put(route('reparaciones.update', $order), [
            ...repairPhotoPayload(),
            'labor_cost' => 50,
            'advance_payment' => 100,
            'device_photos' => fakeRepairPhotos(1, 'reemplazo'),
        ])
        ->assertRedirect(route('reparaciones.edit', $order))
        ->assertSessionHasErrors('repair');

    expect($order->photos()->count())->toBe(1);
    Storage::disk('public')->assertExists($originalPath);
    expect(Storage::disk('public')->allFiles('repair-orders'))->toBe([$originalPath]);
});

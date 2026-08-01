<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function productImageAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

function productImagePayload(Category $category, string $code): array
{
    return [
        'category_id' => $category->id,
        'name' => 'Producto con imagen',
        'code' => $code,
        'purchase_price' => 75,
        'sale_price' => 100,
        'stock' => 0,
        'unit' => 'unidad',
        'status' => 'active',
    ];
}

test('inventory screens can create categories through their shared route', function () {
    $admin = productImageAdmin();

    $this->actingAs($admin)
        ->get(route('inventario.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->postJson(route('categorias.store'), ['name' => 'Nueva categoría'])
        ->assertCreated()
        ->assertJsonStructure(['id', 'name'])
        ->assertJsonPath('name', 'Nueva categoría');

    $this->assertDatabaseHas('categories', ['name' => 'Nueva categoría']);
});

test('an image can be uploaded while creating a product', function () {
    Storage::fake('public');
    $admin = productImageAdmin();
    $category = Category::create(['name' => 'Productos con fotos']);

    $response = $this->actingAs($admin)->post(route('inventario.store'), [
        ...productImagePayload($category, 'IMG-001'),
        'image' => UploadedFile::fake()->image('producto.webp', 800, 800),
    ]);

    $response->assertRedirect(route('inventario.index'));

    $product = Product::where('code', 'IMG-001')->firstOrFail();
    $storedPath = $product->getRawOriginal('image_url');

    expect($storedPath)->toStartWith('products/')
        ->and($product->image_url)->toContain('/storage/products/');
    Storage::disk('public')->assertExists($storedPath);
});

test('an image can be uploaded from quick product registration', function () {
    Storage::fake('public');
    $admin = productImageAdmin();
    $category = Category::create(['name' => 'Registro rápido']);

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        ...productImagePayload($category, 'IMG-QUICK-001'),
        'image' => UploadedFile::fake()->image('quick-product.png', 600, 600),
    ])->assertRedirect(route('inventario.index'));

    $product = Product::where('code', 'IMG-QUICK-001')->firstOrFail();
    $storedPath = $product->getRawOriginal('image_url');

    expect($storedPath)->toStartWith('products/');
    Storage::disk('public')->assertExists($storedPath);
});

test('a product image can be replaced and removed', function () {
    Storage::fake('public');
    $admin = productImageAdmin();
    $category = Category::create(['name' => 'Productos editables']);
    Storage::disk('public')->put('products/old-image.png', 'old');

    $product = Product::create([
        ...productImagePayload($category, 'IMG-002'),
        'image_url' => 'products/old-image.png',
    ]);

    $updatePayload = productImagePayload($category, 'IMG-002');
    unset($updatePayload['stock']);

    $this->actingAs($admin)->put(route('inventario.update', $product), [
        ...$updatePayload,
        'image' => UploadedFile::fake()->image('replacement.jpg', 1000, 700),
    ])->assertRedirect(route('inventario.index'));

    $newPath = $product->fresh()->getRawOriginal('image_url');
    expect($newPath)->toStartWith('products/')->not->toBe('products/old-image.png');
    Storage::disk('public')->assertMissing('products/old-image.png');
    Storage::disk('public')->assertExists($newPath);

    $this->actingAs($admin)->put(route('inventario.update', $product), [
        ...$updatePayload,
        'remove_image' => '1',
    ])->assertRedirect(route('inventario.index'));

    expect($product->fresh()->getRawOriginal('image_url'))->toBeNull();
    Storage::disk('public')->assertMissing($newPath);
});

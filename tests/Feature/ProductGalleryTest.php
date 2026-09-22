<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function galleryAdmin(): User
{
    test()->seed(InventoryCatalogSeeder::class);

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

/** @return array<int, UploadedFile> */
function fakePhotos(int $count, string $prefix = 'foto'): array
{
    return collect(range(1, $count))
        ->map(fn (int $i) => UploadedFile::fake()->image("{$prefix}-{$i}.jpg", 1000, 800))
        ->all();
}

function galleryProductPayload(array $extra = []): array
{
    return [
        'category_id' => Category::firstOrCreate(['name' => 'Celulares'])->id,
        'name' => 'iPhone 13 con fotos',
        'condition' => 'used',
        'purchase_price' => 300,
        'sale_price' => 420,
        'stock' => 1,
        'base_unit_id' => Unit::query()->where('abbreviation', 'und')->firstOrFail()->id,
        'status' => 'active',
        ...$extra,
    ];
}

function galleryProduct(User $admin, int $photos): Product
{
    test()->actingAs($admin)->post(route('inventario.store'), galleryProductPayload([
        'images' => fakePhotos($photos),
    ]))->assertSessionHasNoErrors();

    return Product::query()->where('name', 'iPhone 13 con fotos')->firstOrFail();
}

test('the pro form saves up to five photos and the first one is the cover', function () {
    Storage::fake('public');
    $admin = galleryAdmin();

    $product = galleryProduct($admin, 5);

    $images = $product->images;
    expect($images)->toHaveCount(5)
        ->and($images->pluck('sort_order')->all())->toBe([0, 1, 2, 3, 4])
        ->and($product->getRawOriginal('image_url'))->toBe($images->first()->path)
        ->and($product->image_url)->toBe($images->first()->url)
        ->and($images->first()->url)->toStartWith('/media/products/');

    foreach ($images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('more than five photos are rejected and nothing is stored', function () {
    Storage::fake('public');
    $admin = galleryAdmin();

    $this->actingAs($admin)->post(route('inventario.store'), galleryProductPayload([
        'images' => fakePhotos(6),
    ]))->assertSessionHasErrors('images');

    expect(Product::query()->where('name', 'iPhone 13 con fotos')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('products'))->toBe([]);
});

test('editing adds photos up to the limit and rejects the sixth', function () {
    Storage::fake('public');
    $admin = galleryAdmin();
    $product = galleryProduct($admin, 3);

    $payload = fn (array $extra) => [
        'category_id' => $product->category_id,
        'name' => $product->name,
        'code' => $product->code,
        'purchase_price' => 300,
        'sale_price' => 420,
        'base_unit_id' => $product->base_unit_id,
        'status' => 'active',
        ...$extra,
    ];

    $this->actingAs($admin)->put(route('inventario.update', $product), $payload([
        'images' => fakePhotos(3, 'extra'),
    ]))->assertSessionHasErrors('images');

    expect($product->images()->count())->toBe(3)
        ->and(Storage::disk('public')->allFiles('products'))->toHaveCount(3);

    $this->actingAs($admin)->put(route('inventario.update', $product), $payload([
        'images' => fakePhotos(2, 'extra'),
    ]))->assertRedirect(route('inventario.index'));

    expect($product->images()->count())->toBe(5);
});

test('removing photos deletes their files and promotes the next one to cover', function () {
    Storage::fake('public');
    $admin = galleryAdmin();
    $product = galleryProduct($admin, 3);
    [$first, $second, $third] = $product->images->all();

    $this->actingAs($admin)->put(route('inventario.update', $product), [
        'category_id' => $product->category_id,
        'name' => $product->name,
        'code' => $product->code,
        'purchase_price' => 300,
        'sale_price' => 420,
        'base_unit_id' => $product->base_unit_id,
        'status' => 'active',
        'remove_image_ids' => [$first->id],
    ])->assertRedirect(route('inventario.index'));

    $product->refresh();

    expect($product->images()->count())->toBe(2)
        ->and($product->getRawOriginal('image_url'))->toBe($second->path)
        ->and(ProductImage::find($first->id))->toBeNull();
    Storage::disk('public')->assertMissing($first->path);
    Storage::disk('public')->assertExists($second->path);
    Storage::disk('public')->assertExists($third->path);

    $this->actingAs($admin)->put(route('inventario.update', $product), [
        'category_id' => $product->category_id,
        'name' => $product->name,
        'code' => $product->code,
        'purchase_price' => 300,
        'sale_price' => 420,
        'base_unit_id' => $product->base_unit_id,
        'status' => 'active',
        'remove_image_ids' => $product->images->pluck('id')->all(),
    ])->assertRedirect(route('inventario.index'));

    expect($product->fresh()->getRawOriginal('image_url'))->toBeNull()
        ->and(Storage::disk('public')->allFiles('products'))->toBe([]);
});

test('the detail page shows the gallery and the edit page lists the current photos', function () {
    Storage::fake('public');
    $admin = galleryAdmin();
    $product = galleryProduct($admin, 3);

    $show = $this->actingAs($admin)->get(route('inventario.show', $product))->assertOk();
    $edit = $this->actingAs($admin)->get(route('inventario.edit', $product))->assertOk();

    foreach ($product->images as $image) {
        $show->assertSee($image->url, false);
        $edit->assertSee($image->url, false);
    }
    $show->assertSee('Fotos del producto');
    $edit->assertSee('name="remove_image_ids[]"', false);
    $edit->assertSee('name="images[]"', false);
});

test('registro rapido still accepts one photo and keeps it as the cover', function () {
    Storage::fake('public');
    $admin = galleryAdmin();
    Category::firstOrCreate(['name' => 'Celulares']);

    $this->actingAs($admin)->post(route('inventario.quick-store'), [
        'name' => 'Funda transparente',
        'sale_price' => 5,
        'image' => UploadedFile::fake()->image('funda.jpg', 800, 800),
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('name', 'Funda transparente')->firstOrFail();

    expect($product->images)->toHaveCount(1)
        ->and($product->getRawOriginal('image_url'))->toBe($product->images->first()->path);
});

test('replacing the cover from the point of sale keeps the gallery in sync', function () {
    Storage::fake('public');
    $admin = galleryAdmin();
    $product = galleryProduct($admin, 2);
    $oldCover = $product->images->first();
    $second = $product->images->last();

    $this->actingAs($admin)->postJson(route('facturacion.pos-product-image', $product), [
        'image' => UploadedFile::fake()->image('nueva-portada.jpg', 900, 900),
    ])->assertOk();

    $product->refresh();

    expect($product->images()->count())->toBe(2)
        ->and($product->getRawOriginal('image_url'))->not->toBe($oldCover->path)
        ->and($product->images->first()->path)->toBe($product->getRawOriginal('image_url'))
        ->and($product->images->last()->path)->toBe($second->path);
    Storage::disk('public')->assertMissing($oldCover->path);
});

test('existing single images are backfilled into the gallery by the migration', function () {
    $migration = require database_path('migrations/2026_09_21_140000_create_product_images_table.php');
    $migration->down();

    $category = Category::firstOrCreate(['name' => 'Celulares']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto con imagen previa',
        'code' => 'LEGACY-1',
        'purchase_price' => 1,
        'sale_price' => 2,
        'stock' => 0,
        'unit' => 'und',
        'status' => 'active',
        'image_url' => 'products/antigua.webp',
    ]);

    $migration->up();

    expect(ProductImage::where('product_id', $product->id)->pluck('path')->all())->toBe(['products/antigua.webp']);
});

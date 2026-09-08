<?php

use App\Http\Controllers\ProformaController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function proformaWarningProduct(string $name, string $code, float $stock): Product
{
    $category = Category::firstOrCreate(['name' => 'Proformas']);

    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'code' => $code,
        'purchase_price' => 5,
        'sale_price' => 10,
        'stock' => $stock,
        'unit' => 'unidad',
        'status' => 'active',
    ]);
}

function invokeProformaStockWarnings(array $items): array
{
    $method = new ReflectionMethod(ProformaController::class, 'stockWarnings');

    return $method->invoke(app(ProformaController::class), $items);
}

test('proforma warns when a product has no stock', function () {
    $product = proformaWarningProduct('Producto agotado', 'PRO-OUT', 0);

    $warnings = invokeProformaStockWarnings([
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('Producto agotado')
        ->and($warnings[0])->toContain('no tiene existencias');
});

test('proforma warns when grouped requested quantity exceeds stock', function () {
    $product = proformaWarningProduct('Producto limitado', 'PRO-LOW', 3);

    $warnings = invokeProformaStockWarnings([
        ['product_id' => $product->id, 'quantity' => 2],
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('solicitaste 4.00')
        ->and($warnings[0])->toContain('solo hay 3.00');
});

test('proforma does not warn when requested quantity is available', function () {
    $product = proformaWarningProduct('Producto disponible', 'PRO-OK', 5);

    expect(invokeProformaStockWarnings([
        ['product_id' => $product->id, 'quantity' => 5],
    ]))->toBe([]);
});

<?php

use App\Models\Branch;
use App\Models\BranchDataImport;
use App\Models\BranchProductMapping;
use App\Models\CajaSession;
use App\Models\Category;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function branchFixture(string $code, float $price): array
{
    $warehouse = Warehouse::query()->create([
        'code' => 'BOD-'.$code,
        'name' => 'Bodega '.$code,
        'is_active' => true,
        'is_default' => false,
    ]);
    $priceList = PriceList::query()->create([
        'code' => 'PRE-'.$code,
        'name' => 'Precios '.$code,
        'is_active' => true,
        'is_default' => false,
    ]);
    $branch = Branch::query()->create([
        'code' => $code,
        'name' => 'Sucursal '.$code,
        'warehouse_id' => $warehouse->id,
        'price_list_id' => $priceList->id,
        'is_active' => true,
    ]);

    return compact('warehouse', 'priceList', 'branch', 'price');
}

test('each branch exposes its own stock and price for the same product', function () {
    Tax::query()->update(['is_default' => false]);
    Tax::query()->create(['code' => 'EX-MB', 'name' => 'Exento', 'rate' => 0, 'is_default' => true, 'is_active' => true]);
    $a = branchFixture('PRI', 110);
    $b = branchFixture('SEC', 135);
    $category = Category::query()->create(['name' => 'Multisucursal']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Producto compartido',
        'code' => 'MULTI-1',
        'purchase_price' => 70,
        'sale_price' => 100,
        'stock' => 15,
        'unit' => 'unidad',
        'status' => 'active',
    ]);

    foreach ([[$a, 10], [$b, 5]] as [$fixture, $quantity]) {
        WarehouseStock::query()->create([
            'warehouse_id' => $fixture['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'purchase_price' => $fixture === $a ? 60 : 80,
        ]);
        PriceListItem::query()->create([
            'price_list_id' => $fixture['priceList']->id,
            'product_id' => $product->id,
            'unit_price' => $fixture['price'],
            'min_quantity' => 1,
        ]);
    }

    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'branch_id' => $a['branch']->id, 'is_active' => true]);
    $user->roles()->sync([$role->id]);
    openCashSessionFor($user);
    CajaSession::currentForUser($user->id)->update(['branch_id' => $a['branch']->id]);

    $this->actingAs($user)
        ->getJson(route('facturacion.pos-products', ['warehouse_id' => $a['warehouse']->id]))
        ->assertOk()
        ->assertJsonFragment(['id' => $product->id, 'sale_price' => 110, 'warehouse_stock' => 10]);

    $this->actingAs($user)
        ->getJson(route('facturacion.pos-products', ['warehouse_id' => $b['warehouse']->id]))
        ->assertStatus(422)
        ->assertJsonPath('message', 'La bodega seleccionada pertenece a Sucursal SEC, pero la caja está abierta en Sucursal PRI.');

    $accounting = Mockery::mock(AccountingService::class);
    $accounting->shouldReceive('recordSale')->once();
    app()->instance(AccountingService::class, $accounting);

    $this->actingAs($user)->post(route('facturacion.pos-store'), [
        'payment_type' => 'cash',
        'warehouse_id' => $a['warehouse']->id,
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 2]]),
        'amount_received' => 500,
        'request_token' => (string) Str::uuid(),
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->firstOrFail();
    expect($sale->branch_id)->toBe($a['branch']->id)
        ->and((float) $sale->total)->toBe(220.0)
        ->and((float) $sale->details()->firstOrFail()->price)->toBe(110.0)
        ->and((float) WarehouseStock::query()->where('warehouse_id', $a['warehouse']->id)->where('product_id', $product->id)->value('quantity'))->toBe(8.0)
        ->and((float) WarehouseStock::query()->where('warehouse_id', $b['warehouse']->id)->where('product_id', $product->id)->value('quantity'))->toBe(5.0);
});

test('branch import is explicit and idempotent for stock prices and receivables', function () {
    $branch = branchFixture('IMP', 0);
    User::factory()->create();
    $productsPath = sys_get_temp_dir().'/products-'.Str::uuid().'.xlsx';
    $receivablesPath = sys_get_temp_dir().'/receivables-'.Str::uuid().'.xlsx';

    $productsBook = new Spreadsheet;
    $productsBook->getActiveSheet()->fromArray([
        ['CodProducto', 'Descripcion', 'Almacen', 'Costo', 'PrecioVenta', 'CodBarra', 'Unidad', 'Categoria', 'Ubicacion'],
        ['P-001', 'Producto importado', 12, 'C$40.00', 'C$65.00', '7430001', 'UNIDAD', 'GENERAL', 'A-1'],
        ['P-002', 'Existencia negativa', -6, 'C$20.00', 'C$15.00', null, 'UNIDAD', 'GENERAL', 'A-2'],
    ]);
    (new Xlsx($productsBook))->save($productsPath);

    $receivablesBook = new Spreadsheet;
    $receivablesBook->getActiveSheet()->fromArray([
        ['Fecha', 'Código', 'Cliente', 'Total', 'Pago', 'Saldo', 'Fecha Máxima'],
        ['01/09/2026', 77, 'Cliente importado', 'C$100.00', 'C$25.00', 'C$75.00', '30/09/2026'],
    ]);
    (new Xlsx($receivablesBook))->save($receivablesPath);

    try {
        expect(Artisan::call('app:import-branch-data', [
            'branch' => $branch['branch']->code,
            'products' => $productsPath,
            'receivables' => $receivablesPath,
        ]))->toBe(0)
            ->and(Product::query()->where('code', 'P-001')->exists())->toBeFalse();

        foreach (range(1, 2) as $attempt) {
            expect(Artisan::call('app:import-branch-data', [
                'branch' => $branch['branch']->code,
                'products' => $productsPath,
                'receivables' => $receivablesPath,
                '--apply' => true,
            ]))->toBe(0);
        }

        $product = Product::query()->where('code', 'IMP-P-001')->firstOrFail();
        $negativeProduct = Product::query()->where('code', 'IMP-P-002')->firstOrFail();
        expect((float) WarehouseStock::query()->where('warehouse_id', $branch['warehouse']->id)->where('product_id', $product->id)->value('quantity'))->toBe(12.0)
            ->and((float) WarehouseStock::query()->where('warehouse_id', $branch['warehouse']->id)->where('product_id', $product->id)->value('purchase_price'))->toBe(40.0)
            ->and((float) PriceListItem::query()->where('price_list_id', $branch['priceList']->id)->where('product_id', $product->id)->value('unit_price'))->toBe(65.0)
            ->and(Sale::query()->where('invoice_number', 'IMP-IMP-77')->count())->toBe(1)
            ->and((float) Sale::query()->where('invoice_number', 'IMP-IMP-77')->value('total'))->toBe(75.0)
            ->and((float) WarehouseStock::query()->where('warehouse_id', $branch['warehouse']->id)->where('product_id', $negativeProduct->id)->value('quantity'))->toBe(0.0)
            ->and((float) PriceListItem::query()->where('price_list_id', $branch['priceList']->id)->where('product_id', $negativeProduct->id)->value('unit_price'))->toBe(15.0)
            ->and(BranchDataImport::query()->count())->toBe(1)
            ->and(BranchProductMapping::query()->where('branch_id', $branch['branch']->id)->count())->toBe(2)
            ->and(InventoryMovement::query()->where('product_id', $product->id)->count())->toBe(1);
    } finally {
        @unlink($productsPath);
        @unlink($receivablesPath);
    }
});

test('branch import namespaces colliding legacy product codes and anonymizes training clients', function () {
    $first = branchFixture('MAIN', 0);
    $second = branchFixture('S2', 0);
    User::factory()->create();
    $productsPath = sys_get_temp_dir().'/products-collision-'.Str::uuid().'.xlsx';
    $receivablesPath = sys_get_temp_dir().'/receivables-training-'.Str::uuid().'.xlsx';

    $productsBook = new Spreadsheet;
    $productsBook->getActiveSheet()->fromArray([
        ['CodProducto', 'Descripcion', 'Almacen', 'Costo', 'PrecioVenta'],
        [490, 'Producto local', 3, 10, 15],
    ]);
    (new Xlsx($productsBook))->save($productsPath);
    $receivablesBook = new Spreadsheet;
    $receivablesBook->getActiveSheet()->fromArray([
        ['Fecha', 'Código', 'Cliente', 'Total', 'Pago', 'Saldo', 'Fecha Máxima'],
        ['01/09/2026', 1, 'Nombre privado', 100, 'C$0.00', 100, '30/09/2026'],
    ]);
    (new Xlsx($receivablesBook))->save($receivablesPath);

    try {
        foreach ([$first, $second] as $fixture) {
            expect(Artisan::call('app:import-branch-data', [
                'branch' => $fixture['branch']->code,
                'products' => $productsPath,
                'receivables' => $receivablesPath,
                '--mode' => 'training',
                '--apply' => true,
            ]))->toBe(0);
        }

        expect(Product::query()->whereIn('code', ['MAIN-490', 'S2-490'])->count())->toBe(2)
            ->and(Client::query()->where('name', 'Nombre privado')->exists())->toBeFalse()
            ->and(Client::query()->where('name', 'like', 'Cliente capacitación %')->count())->toBe(2);
    } finally {
        @unlink($productsPath);
        @unlink($receivablesPath);
    }
});

test('zero sale prices require explicit approval and remain inactive', function () {
    $branch = branchFixture('ZERO', 0);
    User::factory()->create();
    $productsPath = sys_get_temp_dir().'/products-zero-'.Str::uuid().'.xlsx';
    $receivablesPath = sys_get_temp_dir().'/receivables-empty-'.Str::uuid().'.xlsx';

    $productsBook = new Spreadsheet;
    $productsBook->getActiveSheet()->fromArray([
        ['CodProducto', 'Descripcion', 'Almacen', 'Costo', 'PrecioVenta'],
        [1, 'Sin precio', 2, 10, 'C$0.00'],
    ]);
    (new Xlsx($productsBook))->save($productsPath);
    $receivablesBook = new Spreadsheet;
    $receivablesBook->getActiveSheet()->fromArray([
        ['Fecha', 'Código', 'Cliente', 'Total', 'Pago', 'Saldo', 'Fecha Máxima'],
    ]);
    (new Xlsx($receivablesBook))->save($receivablesPath);

    try {
        $arguments = [
            'branch' => $branch['branch']->code,
            'products' => $productsPath,
            'receivables' => $receivablesPath,
            '--apply' => true,
        ];
        expect(Artisan::call('app:import-branch-data', $arguments))->toBe(1)
            ->and(Product::query()->where('code', 'ZERO-1')->exists())->toBeFalse();

        $arguments['--allow-zero-prices'] = true;
        expect(Artisan::call('app:import-branch-data', $arguments))->toBe(0)
            ->and(Product::query()->where('code', 'ZERO-1')->value('status'))->toBe('inactive');
    } finally {
        @unlink($productsPath);
        @unlink($receivablesPath);
    }
});

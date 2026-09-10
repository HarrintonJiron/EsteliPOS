<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class ImportBranchDataCommand extends Command
{
    protected $signature = 'app:import-branch-data
        {branch : Código o ID de la sucursal}
        {products : Archivo XLSX de productos de esa sucursal}
        {receivables : Archivo XLSX de cuentas por cobrar de esa sucursal}
        {--apply : Confirma la escritura; sin esta opción solo valida y resume}';

    protected $description = 'Valida e importa inventario, precios y cuentas por cobrar separados por sucursal';

    public function handle(): int
    {
        $branch = Branch::query()
            ->where('code', $this->argument('branch'))
            ->orWhere('id', $this->argument('branch'))
            ->first();
        if (! $branch?->warehouse_id) {
            $this->error('La sucursal no existe o no tiene una bodega vinculada.');

            return self::FAILURE;
        }

        try {
            $products = $this->readProducts((string) $this->argument('products'));
            $receivables = $this->readReceivables((string) $this->argument('receivables'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Control', 'Resultado'], [
            ['Sucursal', $branch->name],
            ['Bodega', $branch->warehouse?->name ?? (string) $branch->warehouse_id],
            ['Productos', count($products)],
            ['Existencia total', number_format(array_sum(array_column($products, 'stock')), 4)],
            ['Cuentas por cobrar', count($receivables)],
            ['Saldo por cobrar', 'C$ '.number_format(array_sum(array_column($receivables, 'balance')), 2)],
        ]);
        $belowCost = count(array_filter($products, fn (array $row) => $row['price'] < $row['cost']));
        if ($belowCost > 0) {
            $this->warn("{$belowCost} productos tienen precio de venta menor que su costo; confirma que sea intencional.");
        }
        $normalizedStock = count(array_filter($products, fn (array $row) => $row['source_stock'] < 0));
        if ($normalizedStock > 0) {
            $this->warn("{$normalizedStock} productos tenían existencia negativa y se importarán con existencia 0.");
        }

        if (! $this->option('apply')) {
            $this->warn('Vista previa solamente. Repite con --apply después de verificar la sucursal y los archivos.');

            return self::SUCCESS;
        }

        DB::transaction(fn () => $this->import($branch, $products, $receivables));
        $this->info('Importación aplicada y separada por sucursal correctamente.');

        return self::SUCCESS;
    }

    /** @return list<array{code:string,name:string,stock:float,source_stock:float,cost:float,price:float,barcode:?string,unit:string,category:string,location:?string}> */
    private function readProducts(string $path): array
    {
        $rows = $this->rows($path);
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?: []);
        foreach (['CodProducto', 'Descripcion', 'Almacen', 'Costo', 'PrecioVenta'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException("El archivo de productos no contiene la columna {$required}.");
            }
        }

        $products = [];
        foreach ($rows as $rowNumber => $row) {
            $row = array_combine($headers, array_pad($row, count($headers), null));
            $code = trim((string) ($row['CodProducto'] ?? ''));
            if ($code === '') {
                continue;
            }
            if (isset($products[$code])) {
                throw new RuntimeException('Código de producto duplicado: '.$code.'.');
            }
            $stock = $this->number($row['Almacen'] ?? null, 'Almacen', $rowNumber + 2);
            $cost = $this->number($row['Costo'] ?? null, 'Costo', $rowNumber + 2);
            $price = $this->number($row['PrecioVenta'] ?? null, 'PrecioVenta', $rowNumber + 2);
            if ($cost < 0 || $price < 0 || $price > 9_999_999.99) {
                throw new RuntimeException('Valores fuera de rango en productos, fila '.($rowNumber + 2).'.');
            }
            $products[$code] = [
                'code' => $code,
                'name' => trim((string) ($row['Descripcion'] ?? '')) ?: 'Producto '.$code,
                'stock' => max(0, $stock),
                'source_stock' => $stock,
                'cost' => $cost,
                'price' => $price,
                'barcode' => filled($row['CodBarra'] ?? null) ? trim((string) $row['CodBarra']) : null,
                'unit' => trim((string) ($row['Unidad'] ?? 'UNIDAD')) ?: 'UNIDAD',
                'category' => trim((string) ($row['Categoria'] ?? 'GENERAL')) ?: 'GENERAL',
                'location' => filled($row['Ubicacion'] ?? null) ? trim((string) $row['Ubicacion']) : null,
            ];
        }

        return array_values($products);
    }

    /** @return list<array{legacy_code:string,date:mixed,client:string,total:float,paid:float,balance:float,due_date:mixed}> */
    private function readReceivables(string $path): array
    {
        $rows = $this->rows($path);
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?: []);
        foreach (['Fecha', 'Código', 'Cliente', 'Total', 'Pago', 'Saldo', 'Fecha Máxima'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException("El archivo de cuentas por cobrar no contiene la columna {$required}.");
            }
        }

        $receivables = [];
        foreach ($rows as $rowNumber => $row) {
            $row = array_combine($headers, array_pad($row, count($headers), null));
            if (! is_numeric($row['Código'] ?? null)) {
                continue;
            }
            $balance = $this->number($row['Saldo'] ?? null, 'Saldo', $rowNumber + 2);
            if ($balance <= 0 || $balance > 9_999_999.99) {
                continue;
            }
            $receivables[] = [
                'legacy_code' => trim((string) $row['Código']),
                'date' => $this->date($row['Fecha'] ?? null, 'Fecha', $rowNumber + 2),
                'client' => trim((string) $row['Cliente']) ?: 'Cliente sin nombre',
                'total' => $this->number($row['Total'] ?? null, 'Total', $rowNumber + 2),
                'paid' => $this->number($row['Pago'] ?? null, 'Pago', $rowNumber + 2),
                'balance' => $balance,
                'due_date' => $this->date($row['Fecha Máxima'] ?? null, 'Fecha Máxima', $rowNumber + 2),
            ];
        }

        return $receivables;
    }

    private function import(Branch $branch, array $products, array $receivables): void
    {
        $userId = User::query()->orderBy('id')->value('id');
        if (! $userId) {
            throw new RuntimeException('Debe existir al menos un usuario antes de importar cuentas por cobrar.');
        }

        $unit = Unit::query()->where('abbreviation', 'und')->first()
            ?? Unit::query()->create(['name' => 'Unidad', 'abbreviation' => 'und', 'is_active' => true]);
        $priceList = $branch->priceList ?? PriceList::query()->create([
            'code' => 'SUC-'.Str::upper(Str::slug($branch->code, '-')),
            'name' => 'Precios '.$branch->name,
            'description' => 'Lista operativa separada para '.$branch->name,
            'is_default' => false,
            'is_active' => true,
        ]);
        $branch->update(['price_list_id' => $priceList->id]);

        foreach ($products as $row) {
            $category = Category::query()->firstOrCreate(['name' => $row['category']]);
            $product = Product::query()->firstOrCreate(['code' => $row['code']], [
                'category_id' => $category->id,
                'name' => $row['name'],
                'purchase_price' => $row['cost'],
                'sale_price' => $row['price'],
                'stock' => 0,
                'unit' => $unit->abbreviation,
                'base_unit_id' => $unit->id,
                'location' => $row['location'],
                'status' => 'active',
            ]);
            $stock = WarehouseStock::query()->updateOrCreate(
                ['warehouse_id' => $branch->warehouse_id, 'product_id' => $product->id],
                ['quantity' => $row['stock'], 'purchase_price' => $row['cost'], 'aisle' => $row['location']],
            );
            PriceListItem::query()->updateOrCreate([
                'price_list_id' => $priceList->id,
                'product_id' => $product->id,
                'unit_id' => $product->base_unit_id,
                'min_quantity' => 1,
            ], ['unit_price' => $row['price']]);
            $product->update(['stock' => WarehouseStock::query()->where('product_id', $product->id)->sum('quantity')]);
        }

        foreach ($receivables as $row) {
            $client = Client::query()->firstOrCreate(
                ['name' => $row['client']],
                ['phone' => null, 'status' => 'active', 'credit_enabled' => true, 'credit_limit' => 0],
            );
            Sale::query()->updateOrCreate([
                'invoice_number' => 'IMP-'.$branch->code.'-'.$row['legacy_code'],
            ], [
                'client_id' => $client->id,
                'user_id' => $userId,
                'branch_id' => $branch->id,
                'warehouse_id' => $branch->warehouse_id,
                'billing_name' => $client->name,
                'date' => $row['date'],
                'due_date' => $row['due_date'],
                'subtotal' => $row['balance'],
                'tax_total' => 0,
                'total' => $row['balance'],
                'payment_type' => 'credit',
                'status' => 'pending',
                'tax_included' => false,
                'tax_rate' => 0,
                'notes' => "Saldo inicial importado. Documento original {$row['legacy_code']}; total C$ {$row['total']}; pagado C$ {$row['paid']}.",
            ]);
        }
    }

    /** @return list<array<int, mixed>> */
    private function rows(string $path): array
    {
        if (! is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('No se encontró un archivo XLSX válido: '.$path);
        }

        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
    }

    private function number(mixed $value, string $field, int $row): float
    {
        if (is_string($value)) {
            $value = str_replace(['C$', '$', ',', ' '], '', trim($value));
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("{$field} no es numérico en la fila {$row}.");
        }

        return round((float) $value, 4);
    }

    private function date(mixed $value, string $field, int $row): string
    {
        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            $text = trim((string) $value);
            foreach (['d/m/Y', 'Y-m-d'] as $format) {
                $date = Carbon::createFromFormat('!'.$format, $text);
                if ($date !== false && $date->format($format) === $text) {
                    return $date->toDateString();
                }
            }

            throw new RuntimeException;
        } catch (\Throwable) {
            throw new RuntimeException("{$field} no contiene una fecha válida en la fila {$row}.");
        }
    }
}

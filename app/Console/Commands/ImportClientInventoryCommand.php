<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportClientInventoryCommand extends Command
{
    protected $signature = 'app:import-client-inventory
        {csv : CSV validado generado por scripts/extract-client-inventory.py}
        {--replace-demo : Reconstruye la base y conserva las cuentas de usuario}
        {--force : Autoriza la sustitución no interactiva en producción}';

    protected $description = 'Valida o importa el inventario inicial del cliente con trazabilidad y stock por bodega';

    public function handle(): int
    {
        try {
            $rows = $this->readRows((string) $this->argument('csv'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Métrica', 'Valor'], [
            ['Productos', count($rows)],
            ['Códigos únicos', count(array_unique(array_column($rows, 'code')))],
            ['Categorías', count(array_unique(array_column($rows, 'category')))],
            ['Con existencia', count(array_filter($rows, fn (array $row) => $row['stock'] > 0))],
            ['Existencia cero', count(array_filter($rows, fn (array $row) => $row['stock'] == 0))],
            ['Requieren revisión', count(array_filter($rows, fn (array $row) => $row['requires_review'] !== ''))],
        ]);

        if (! $this->option('replace-demo')) {
            $this->info('Vista previa válida. No se modificó la base de datos.');

            return self::SUCCESS;
        }
        if (! $this->option('force')) {
            $this->error('La sustitución requiere --force y un respaldo SQLite verificado.');

            return self::FAILURE;
        }

        $users = $this->snapshotUsers();
        try {
            if (Artisan::call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
                throw new RuntimeException('No se pudo reconstruir el esquema.');
            }
            if (Artisan::call('db:seed', ['--class' => ProductionSeeder::class, '--force' => true]) !== self::SUCCESS) {
                throw new RuntimeException('No se pudieron instalar los catálogos base.');
            }

            $administrator = $this->restoreUsers($users);
            $this->importRows($rows, $administrator);
            Artisan::call('optimize:clear');
            Artisan::call('optimize');
        } catch (Throwable $exception) {
            $this->error('La carga falló después de iniciar la limpieza: '.$exception->getMessage());
            $this->error('Restaure inmediatamente el respaldo SQLite previo.');

            return self::FAILURE;
        }

        $this->info('Inventario del cliente cargado correctamente.');

        return self::SUCCESS;
    }

    /** @return list<array{category:string,code:string,name:string,sale_price:float,stock:float,source_page:int,requires_review:string}> */
    private function readRows(string $path): array
    {
        $resolved = realpath($path);
        if (! $resolved || ! is_readable($resolved)) {
            throw new RuntimeException('No se puede leer el CSV indicado.');
        }
        $handle = fopen($resolved, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el CSV.');
        }
        $headers = fgetcsv($handle, escape: '');
        if (! $headers) {
            throw new RuntimeException('El CSV no contiene encabezados.');
        }
        $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        $required = ['source_page', 'category', 'code', 'name', 'sale_price', 'import_stock', 'requires_review'];
        if (array_diff($required, $headers)) {
            throw new RuntimeException('El CSV no contiene todas las columnas requeridas.');
        }

        $rows = [];
        while (($values = fgetcsv($handle, escape: '')) !== false) {
            if (count($values) !== count($headers)) {
                throw new RuntimeException('Existe una fila CSV incompleta.');
            }
            $row = array_combine($headers, $values);
            $rows[] = [
                'source_page' => (int) $row['source_page'],
                'category' => trim($row['category']),
                'code' => trim($row['code']),
                'name' => trim($row['name']),
                'sale_price' => round((float) $row['sale_price'], 2),
                'stock' => round(max(0, (float) $row['import_stock']), 4),
                'requires_review' => trim($row['requires_review']),
            ];
        }
        fclose($handle);

        if ($rows === [] || count($rows) !== count(array_unique(array_column($rows, 'code')))) {
            throw new RuntimeException('El CSV está vacío o contiene códigos duplicados.');
        }

        return $rows;
    }

    /** @return list<array{attributes:array<string,mixed>,roles:list<string>,permissions:list<string>}> */
    private function snapshotUsers(): array
    {
        return User::query()->with(['roles', 'directPermissions'])->get()->map(function (User $user): array {
            $attributes = $user->getAttributes();
            unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

            return [
                'attributes' => $attributes,
                'roles' => $user->roles->pluck('slug')->all(),
                'permissions' => $user->directPermissions->pluck('slug')->all(),
            ];
        })->all();
    }

    /** @param list<array{attributes:array<string,mixed>,roles:list<string>,permissions:list<string>}> $snapshots */
    private function restoreUsers(array $snapshots): User
    {
        $administrator = null;
        foreach ($snapshots as $snapshot) {
            $user = new User;
            $user->forceFill($snapshot['attributes'])->save();
            $user->roles()->sync(Role::query()->whereIn('slug', $snapshot['roles'])->pluck('id'));
            $user->directPermissions()->sync(Permission::query()->whereIn('slug', $snapshot['permissions'])->pluck('id'));
            if ($administrator === null && ($user->role === 'admin' || $user->isAdmin())) {
                $administrator = $user;
            }
        }

        return $administrator ?? throw new RuntimeException('No existe una cuenta administrativa para conservar.');
    }

    /** @param list<array{category:string,code:string,name:string,sale_price:float,stock:float,source_page:int,requires_review:string}> $rows */
    private function importRows(array $rows, User $administrator): void
    {
        DB::transaction(function () use ($rows, $administrator): void {
            $unit = Unit::query()->where('abbreviation', 'und')->firstOrFail();
            $warehouse = Warehouse::default() ?? throw new RuntimeException('No existe bodega principal.');
            $priceList = PriceList::query()->where('is_default', true)->firstOrFail();
            $categories = [];

            foreach ($rows as $row) {
                $category = $categories[$row['category']] ??= Category::query()->firstOrCreate(
                    ['name' => $row['category']],
                    ['description' => 'Importada del inventario del cliente']
                );
                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'purchase_price' => 0,
                    'sale_price' => $row['sale_price'],
                    'stock' => $row['stock'],
                    'unit' => 'und',
                    'base_unit_id' => $unit->id,
                    'low_stock_threshold' => 5,
                    'status' => 'active',
                    'observations' => 'Importado del corte de inventario, página '.$row['source_page'].($row['requires_review'] ? '. REVISAR: '.$row['requires_review'] : ''),
                ]);
                PriceListItem::query()->create([
                    'price_list_id' => $priceList->id,
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'unit_price' => $row['sale_price'],
                    'min_quantity' => 1,
                ]);
                if ($row['stock'] > 0) {
                    WarehouseStock::query()->create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'quantity' => $row['stock'],
                    ]);
                    InventoryMovement::query()->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'in',
                        'quantity' => $row['stock'],
                        'stock_after' => $row['stock'],
                        'reference' => 'IMPORT-CISVE-20260819',
                        'note' => 'Existencia inicial importada del sistema anterior',
                        'user_id' => $administrator->id,
                    ]);
                }
            }

            AuditLog::query()->create([
                'user_id' => $administrator->id,
                'action' => 'client_inventory.imported',
                'description' => 'Inventario inicial del cliente importado desde sistema anterior',
                'new_values' => ['products' => count($rows), 'source' => 'Inventario Agosto 2026'],
            ]);
        });
    }
}

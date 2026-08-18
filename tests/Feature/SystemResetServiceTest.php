<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\SystemResetService;
use Database\Seeders\ClientDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->originalDefaultConnection = config('database.default');
    $this->originalSqliteDatabase = config('database.connections.sqlite.database');
    $this->resetDatabase = tempnam(sys_get_temp_dir(), 'estelipos-reset-');
    $this->resetBackups = [];

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => $this->resetDatabase,
        'database.connections.sqlite.journal_mode' => 'WAL',
    ]);
    DB::purge('sqlite');

    expect(Artisan::call('migrate:fresh', ['--force' => true]))->toBe(0);
    expect(Artisan::call('db:seed', ['--class' => ProductionSeeder::class, '--force' => true]))->toBe(0);
});

afterEach(function () {
    DB::disconnect('sqlite');
    DB::purge('sqlite');

    File::delete(array_merge(
        [$this->resetDatabase, $this->resetDatabase.'-wal', $this->resetDatabase.'-shm'],
        $this->resetBackups,
    ));

    config([
        'database.default' => $this->originalDefaultConnection,
        'database.connections.sqlite.database' => $this->originalSqliteDatabase,
    ]);
    DB::purge('sqlite');
});

function isolatedResetAdministrator(): User
{
    $administrator = User::query()->create([
        'name' => 'Administrador de pruebas',
        'username' => 'admin.reset',
        'email' => 'admin.reset@example.test',
        'password' => 'Password123!',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $role = Role::query()->where('slug', 'admin')->firstOrFail();
    $administrator->roles()->sync([$role->id]);
    $permissionIds = Permission::query()->pluck('id')->all();
    $role->permissions()->sync($permissionIds);
    $administrator->directPermissions()->sync($permissionIds);

    return $administrator;
}

test('clean reset creates a valid backup and preserves only the executing administrator', function () {
    $administrator = isolatedResetAdministrator();
    User::query()->create([
        'name' => 'Usuario temporal',
        'email' => 'temporal@example.test',
        'password' => 'Password123!',
        'is_active' => true,
    ]);

    $result = app(SystemResetService::class)->reset($administrator, 'clean');
    $backup = storage_path('app/backups/'.$result['backup_name']);
    $this->resetBackups[] = $backup;

    expect(is_file($backup))->toBeTrue()
        ->and(filesize($backup))->toBeGreaterThan(0)
        ->and(User::query()->count())->toBe(1)
        ->and(User::query()->where('email', 'temporal@example.test')->exists())->toBeFalse()
        ->and(Product::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'system.reset')->exists())->toBeTrue();

    $restored = User::query()->where('email', 'admin.reset@example.test')->firstOrFail();
    expect(Hash::check('Password123!', $restored->password))->toBeTrue()
        ->and($restored->hasPermission('configuracion.reset_system'))->toBeTrue();
});

test('demo reset loads repeatable commercial data after rebuilding the database', function () {
    $administrator = isolatedResetAdministrator();

    $result = app(SystemResetService::class)->reset($administrator, 'demo');
    $this->resetBackups[] = storage_path('app/backups/'.$result['backup_name']);

    expect(Product::query()->count())->toBeGreaterThan(0)
        ->and(Purchase::query()->count())->toBeGreaterThan(0)
        ->and(Sale::query()->count())->toBeGreaterThan(0)
        ->and(User::query()->where('email', 'admin.reset@example.test')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'system.reset')->exists())->toBeTrue();
});

test('a failure after rebuilding automatically restores the previous sqlite database', function () {
    $administrator = isolatedResetAdministrator();
    User::query()->create([
        'name' => 'Debe sobrevivir',
        'email' => 'sobrevive@example.test',
        'password' => 'Password123!',
        'is_active' => true,
    ]);
    $existingBackups = glob(storage_path('app/backups/antes-reinicio-*.sqlite')) ?: [];

    app()->bind(ClientDemoSeeder::class, fn () => new class extends ClientDemoSeeder
    {
        public function run(): void
        {
            throw new RuntimeException('Fallo provocado después de reconstruir.');
        }
    });

    expect(fn () => app(SystemResetService::class)->reset($administrator, 'demo'))
        ->toThrow(RuntimeException::class, 'la base anterior fue restaurada');

    $newBackups = array_values(array_diff(
        glob(storage_path('app/backups/antes-reinicio-*.sqlite')) ?: [],
        $existingBackups,
    ));
    $this->resetBackups = array_merge($this->resetBackups, $newBackups);

    expect(User::query()->where('email', 'sobrevive@example.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin.reset@example.test')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'system.reset')->exists())->toBeFalse()
        ->and($newBackups)->toHaveCount(1);
});

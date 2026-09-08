<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ClientDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class SystemResetService
{
    /**
     * @return array{backup_name: string, mode: string}
     */
    public function reset(User $administrator, string $mode): array
    {
        if (! in_array($mode, ['clean', 'demo'], true)) {
            throw new RuntimeException('El modo de reinicio no es válido.');
        }

        $lock = $this->acquireLock();
        $backup = null;

        try {
            $snapshot = $this->administratorSnapshot($administrator);
            $backup = $this->backupSqliteDatabase();

            $this->runArtisan('migrate:fresh', ['--force' => true]);
            $this->runArtisan('db:seed', ['--class' => ProductionSeeder::class, '--force' => true]);

            $restoredAdministrator = $this->restoreAdministrator($snapshot);

            if ($mode === 'demo') {
                $this->runArtisan('db:seed', ['--class' => ClientDemoSeeder::class, '--force' => true]);
            }

            AuditLog::query()->create([
                'user_id' => $restoredAdministrator->id,
                'action' => 'system.reset',
                'description' => $mode === 'demo'
                    ? 'Sistema reiniciado con datos de demostración'
                    : 'Sistema reiniciado en limpio',
                'new_values' => ['mode' => $mode, 'backup_name' => $backup['name']],
            ]);

            Artisan::call('optimize:clear');

            return ['backup_name' => $backup['name'], 'mode' => $mode];
        } catch (Throwable $exception) {
            $recovered = $backup !== null && $this->restoreBackup($backup);

            throw new RuntimeException(
                $recovered
                    ? 'No se pudo reiniciar el sistema y la base anterior fue restaurada. '.$exception->getMessage()
                    : 'No se pudo reiniciar el sistema. Revisa el respaldo antes de continuar. '.$exception->getMessage(),
                previous: $exception,
            );
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return resource */
    private function acquireLock()
    {
        $directory = storage_path('framework/cache/data');
        File::ensureDirectoryExists($directory);
        $handle = fopen($directory.'/system-reset.lock', 'c+');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            throw new RuntimeException('Ya hay un reinicio del sistema en ejecución.');
        }

        return $handle;
    }

    /** @return array<string, mixed> */
    private function administratorSnapshot(User $administrator): array
    {
        $administrator->refresh();

        return $administrator->only([
            'name',
            'username',
            'email',
            'phone',
            'role',
            'password',
            'is_active',
            'profile_photo',
            'force_password_change',
            'password_changed_at',
        ]);
    }

    /** @return array{name: string, path: string, database: string} */
    private function backupSqliteDatabase(): array
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('El reinicio desde Configuración solo está habilitado para instalaciones SQLite.');
        }

        $database = (string) $connection->getDatabaseName();
        if ($database === '' || $database === ':memory:' || ! is_file($database)) {
            throw new RuntimeException('No se encontró una base SQLite persistente para respaldar.');
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $backupName = 'antes-reinicio-'.now()->format('Ymd-His-u').'.sqlite';
        $destination = $directory.'/'.$backupName;

        $connection->statement('PRAGMA wal_checkpoint(FULL)');
        $quotedDestination = str_replace("'", "''", $destination);
        $connection->unprepared("VACUUM INTO '{$quotedDestination}'");

        if (! is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('No fue posible verificar el respaldo de la base de datos.');
        }

        return ['name' => $backupName, 'path' => $destination, 'database' => $database];
    }

    /** @param array{name: string, path: string, database: string} $backup */
    private function restoreBackup(array $backup): bool
    {
        try {
            DB::disconnect();
            File::delete($backup['database'].'-wal', $backup['database'].'-shm');

            if (! File::copy($backup['path'], $backup['database'])) {
                return false;
            }

            DB::reconnect();

            return is_file($backup['database']) && filesize($backup['database']) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function restoreAdministrator(array $snapshot): User
    {
        $administrator = User::query()->create($snapshot);
        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $administrator->roles()->sync([$adminRole->id]);

        $permissionIds = Permission::query()->pluck('id')->all();
        $adminRole->permissions()->sync($permissionIds);
        $administrator->directPermissions()->sync($permissionIds);

        return $administrator;
    }

    /** @param array<string, mixed> $arguments */
    private function runArtisan(string $command, array $arguments): void
    {
        if (Artisan::call($command, $arguments) !== 0) {
            throw new RuntimeException("Falló el comando {$command}.");
        }
    }
}

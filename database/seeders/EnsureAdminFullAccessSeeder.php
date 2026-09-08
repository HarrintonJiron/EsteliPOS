<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureAdminFullAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'slug')) {
            $this->command?->warn('No se pudo sincronizar acceso admin: faltan tablas base RBAC (users/roles/slug).');

            return;
        }

        $user = User::query()->where('email', 'admin@agroservicio.com')->first();
        if (! $user) {
            $this->command?->warn('No se encontro el usuario admin@agroservicio.com para sincronizar acceso total.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $adminRole = Role::query()->firstOrCreate(
                ['slug' => 'admin'],
                [
                    'name' => 'Administrador',
                    'description' => 'Acceso total al sistema',
                    'is_system' => true,
                ]
            );

            if (Schema::hasColumn('users', 'role') && $user->role !== 'admin') {
                $user->forceFill(['role' => 'admin'])->save();
            }

            if (Schema::hasTable('role_user')) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            }

            if (Schema::hasTable('permissions')) {
                $permissionIds = Permission::query()->pluck('id')->all();

                if ($permissionIds !== [] && Schema::hasTable('permission_role')) {
                    $adminRole->permissions()->syncWithoutDetaching($permissionIds);
                }

                if ($permissionIds !== [] && Schema::hasTable('permission_user')) {
                    $user->directPermissions()->syncWithoutDetaching($permissionIds);
                }
            }

            if (Schema::hasTable('modules') && Schema::hasTable('module_role')) {
                $moduleIds = Module::query()->pluck('id')->all();
                if ($moduleIds !== []) {
                    $adminRole->modules()->syncWithoutDetaching($moduleIds);
                }
            }
        });

        $this->command?->info('Acceso total del administrador sincronizado correctamente.');
    }
}
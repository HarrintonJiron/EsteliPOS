<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_role_merge_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canonical_role_id');
            $table->unsignedBigInteger('duplicate_role_id');
            $table->json('role_data');
            $table->json('duplicate_user_ids');
            $table->json('duplicate_permission_ids');
            $table->json('canonical_user_ids');
            $table->json('canonical_permission_ids');
            $table->timestamps();
        });

        $this->ensureBaseRolesAndPermissions();

        $duplicateSlugs = DB::table('roles')->select('slug')
            ->whereNotNull('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->pluck('slug');

        foreach ($duplicateSlugs as $slug) {
            $roles = DB::table('roles')->where('slug', $slug)->orderByDesc('is_system')->orderBy('id')->get();
            $canonical = $roles->first();
            $canonicalUsers = DB::table('role_user')->where('role_id', $canonical->id)->pluck('user_id')->all();
            $canonicalPermissions = DB::table('permission_role')->where('role_id', $canonical->id)->pluck('permission_id')->all();

            foreach ($roles->skip(1) as $duplicate) {
                $duplicateUsers = DB::table('role_user')->where('role_id', $duplicate->id)->pluck('user_id')->all();
                $duplicatePermissions = DB::table('permission_role')->where('role_id', $duplicate->id)->pluck('permission_id')->all();

                DB::table('rbac_role_merge_backups')->insert([
                    'canonical_role_id' => $canonical->id,
                    'duplicate_role_id' => $duplicate->id,
                    'role_data' => json_encode((array) $duplicate, JSON_UNESCAPED_UNICODE),
                    'duplicate_user_ids' => json_encode($duplicateUsers),
                    'duplicate_permission_ids' => json_encode($duplicatePermissions),
                    'canonical_user_ids' => json_encode($canonicalUsers),
                    'canonical_permission_ids' => json_encode($canonicalPermissions),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($duplicateUsers as $userId) {
                    $this->attachRole((int) $userId, (int) $canonical->id);
                }
                foreach ($duplicatePermissions as $permissionId) {
                    DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $canonical->id]);
                }

                DB::table('roles')->where('id', $duplicate->id)->delete();
            }

            DB::table('roles')->where('id', $canonical->id)->update([
                'is_system' => $roles->contains(fn ($role) => (bool) $role->is_system),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->whereNotNull('role')->select('id', 'role')->get() as $user) {
            $roleId = DB::table('roles')->where('slug', $user->role)->value('id');
            if ($roleId) {
                $this->attachRole((int) $user->id, (int) $roleId);
            }
        }

        $this->deduplicateRoleUsers();

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug', 'roles_slug_unique');
        });
        Schema::table('role_user', function (Blueprint $table) {
            $table->unique(['role_id', 'user_id'], 'role_user_role_id_user_id_unique');
        });

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminId) {
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $adminId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique('role_user_role_id_user_id_unique');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_slug_unique');
        });

        $groups = DB::table('rbac_role_merge_backups')->orderBy('id')->get()->groupBy('canonical_role_id');
        foreach ($groups as $canonicalId => $backups) {
            $first = $backups->first();
            DB::table('role_user')->where('role_id', $canonicalId)->delete();
            DB::table('permission_role')->where('role_id', $canonicalId)->delete();

            foreach (json_decode($first->canonical_user_ids, true) ?: [] as $userId) {
                $this->attachRole((int) $userId, (int) $canonicalId);
            }
            foreach (json_decode($first->canonical_permission_ids, true) ?: [] as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $canonicalId]);
            }

            foreach ($backups as $backup) {
                DB::table('roles')->insert(json_decode($backup->role_data, true));
                foreach (json_decode($backup->duplicate_user_ids, true) ?: [] as $userId) {
                    $this->attachRole((int) $userId, (int) $backup->duplicate_role_id);
                }
                foreach (json_decode($backup->duplicate_permission_ids, true) ?: [] as $permissionId) {
                    DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $backup->duplicate_role_id]);
                }
            }
        }

        Schema::dropIfExists('rbac_role_merge_backups');
    }

    private function ensureBaseRolesAndPermissions(): void
    {
        $now = now();
        DB::table('roles')->updateOrInsert(['slug' => 'user'], [
            'name' => 'Usuario básico', 'description' => 'Acceso básico sin privilegios administrativos',
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ([
            ['view', 'Ver configuración'],
            ['edit', 'Editar configuración'],
            ['manage_users', 'Gestionar usuarios'],
            ['manage_roles', 'Gestionar roles'],
            ['manage_permissions', 'Gestionar permisos'],
        ] as [$action, $name]) {
            DB::table('permissions')->updateOrInsert(['slug' => "configuracion.{$action}"], [
                'name' => $name, 'module' => 'configuracion', 'action' => $action,
                'description' => "Permiso para {$name}", 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function attachRole(int $userId, int $roleId): void
    {
        if (! DB::table('role_user')->where('user_id', $userId)->where('role_id', $roleId)->exists()) {
            DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId]);
        }
    }

    private function deduplicateRoleUsers(): void
    {
        if (! Schema::hasColumn('role_user', 'id')) {
            return;
        }

        $duplicates = DB::table('role_user')->select('role_id', 'user_id')
            ->groupBy('role_id', 'user_id')->havingRaw('COUNT(*) > 1')->get();
        foreach ($duplicates as $duplicate) {
            $ids = DB::table('role_user')->where('role_id', $duplicate->role_id)
                ->where('user_id', $duplicate->user_id)->orderBy('id')->pluck('id');
            DB::table('role_user')->whereIn('id', $ids->skip(1))->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'configuracion.reset_system'],
            [
                'name' => 'Configuración Reiniciar sistema',
                'module' => 'configuracion',
                'action' => 'reset_system',
                'description' => 'Permite borrar los datos y reconstruir el sistema limpio o con datos demo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $roleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'configuracion.reset_system')->value('id');

        if ($roleId && $permissionId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'configuracion.reset_system')->value('id');
        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }
        if (Schema::hasTable('permission_user')) {
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
        }
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};

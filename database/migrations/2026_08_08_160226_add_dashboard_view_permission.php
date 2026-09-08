<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['slug' => 'dashboard.view'], [
            'name' => 'Dashboard View',
            'module' => 'dashboard',
            'action' => 'view',
            'description' => 'Ver el dashboard general del sistema',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where('slug', 'dashboard.view')->value('id');
        if (! $permissionId) {
            return;
        }

        foreach (['admin', 'supervisor'] as $roleSlug) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if ($roleId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'dashboard.view')->value('id');
        if (! $permissionId) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        DB::table('permission_user')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};

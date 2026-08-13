<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['module' => 'planilla', 'action' => 'delete'],
            ['module' => 'planilla', 'action' => 'approve'],
            ['module' => 'planilla', 'action' => 'pay'],
        ];

        foreach ($permissions as $permission) {
            $slug = $permission['module'].'.'.$permission['action'];
            DB::table('permissions')->updateOrInsert(['slug' => $slug], [
                'name' => ucfirst($permission['module']).' '.ucfirst($permission['action']),
                'module' => $permission['module'],
                'action' => $permission['action'],
                'description' => "Permiso para {$permission['action']} en {$permission['module']}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', ['planilla.delete', 'planilla.approve', 'planilla.pay'])
                ->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $adminId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('slug', ['planilla.delete', 'planilla.approve', 'planilla.pay'])
            ->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->string('order_type', 20)->default('repair')->after('order_number');
            $table->index(['order_type', 'status', 'received_date'], 'repair_orders_type_status_date_idx');
        });

        DB::table('modules')->updateOrInsert(['slug' => 'joyeria'], [
            'name' => 'Joyería', 'description' => 'Taller y órdenes de joyería', 'icon' => '💎',
            'route' => 'joyeria.index', 'dependencies' => json_encode(['clientes', 'inventario']),
            'required_permission' => 'joyeria.view', 'is_core' => false, 'is_active' => false,
            'sort_order' => 13, 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            DB::table('permissions')->updateOrInsert(['slug' => "joyeria.{$action}"], [
                'name' => ucfirst($action).' joyería', 'module' => 'joyeria', 'action' => $action,
                'description' => ucfirst($action).' órdenes del taller de joyería',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        $moduleId = DB::table('modules')->where('slug', 'joyeria')->value('id');
        if ($adminId && $moduleId) {
            DB::table('module_role')->insertOrIgnore(['module_id' => $moduleId, 'role_id' => $adminId]);
            foreach (DB::table('permissions')->where('module', 'joyeria')->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $adminId]);
            }
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'joyeria')->value('id');
        if ($moduleId) {
            DB::table('module_role')->where('module_id', $moduleId)->delete();
        }
        DB::table('modules')->where('slug', 'joyeria')->delete();
        $permissionIds = DB::table('permissions')->where('module', 'joyeria')->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropIndex('repair_orders_type_status_date_idx');
            $table->dropColumn('order_type');
        });
    }
};

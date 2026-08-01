<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = [
            'inventario' => ['view', 'create', 'edit', 'delete', 'export', 'adjust'],
            'compras' => ['view', 'create', 'edit', 'delete', 'export', 'approve'],
            'ventas' => ['view', 'create', 'edit', 'delete', 'export'],
            'clientes' => ['view', 'create', 'edit', 'delete', 'export'],
            'proveedores' => ['view', 'create', 'edit', 'delete', 'export'],
            'caja' => ['view', 'open', 'close', 'export'],
            'creditos' => ['view', 'create', 'export'],
            'proformas' => ['view', 'create', 'edit', 'delete', 'export', 'convert'],
            'reparaciones' => ['view', 'create', 'edit', 'delete', 'export'],
            'planilla' => ['view', 'create', 'edit', 'export'],
            'reportes' => ['view', 'export'],
            'contabilidad' => ['view', 'create', 'edit', 'delete', 'export', 'close_period'],
            'configuracion' => ['view', 'edit', 'manage_users', 'manage_roles', 'manage_permissions'],
        ];

        $now = now();
        foreach ($catalog as $module => $actions) {
            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(['slug' => "{$module}.{$action}"], [
                    'name' => ucfirst($module).' '.ucfirst(str_replace('_', ' ', $action)),
                    'module' => $module,
                    'action' => $action,
                    'description' => "Permiso para {$action} en {$module}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $profiles = [
            'cajero' => ['ventas.view', 'ventas.create', 'caja.view', 'caja.open', 'caja.close'],
            'vendedor' => ['ventas.view', 'ventas.create', 'ventas.edit', 'clientes.view', 'clientes.create', 'clientes.edit', 'inventario.view', 'proformas.view', 'proformas.create', 'proformas.edit', 'proformas.convert'],
            'bodega' => ['inventario.view', 'inventario.create', 'inventario.edit', 'inventario.export', 'inventario.adjust'],
            'compras' => ['compras.view', 'compras.create', 'compras.edit', 'compras.export', 'proveedores.view', 'proveedores.create', 'proveedores.edit', 'inventario.view'],
            'contabilidad' => ['contabilidad.view', 'contabilidad.create', 'contabilidad.edit', 'contabilidad.export', 'contabilidad.close_period', 'reportes.view', 'reportes.export', 'compras.view', 'compras.export', 'ventas.view', 'ventas.export'],
            'contable' => ['contabilidad.view', 'contabilidad.create', 'contabilidad.edit', 'contabilidad.export', 'contabilidad.close_period', 'reportes.view', 'reportes.export'],
            'supervisor' => ['ventas.view', 'ventas.edit', 'ventas.export', 'compras.view', 'compras.approve', 'compras.export', 'inventario.view', 'inventario.adjust', 'clientes.view', 'proveedores.view', 'caja.view', 'reportes.view', 'reportes.export', 'creditos.view', 'proformas.view', 'reparaciones.view', 'planilla.view', 'contabilidad.view'],
        ];

        foreach ($profiles as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) continue;
            foreach (DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminId) {
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $adminId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        $modulesAdded = ['creditos', 'proformas', 'reparaciones', 'planilla', 'contabilidad'];
        $permissionIds = DB::table('permissions')->whereIn('module', $modulesAdded)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};

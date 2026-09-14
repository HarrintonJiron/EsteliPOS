<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ADDED_SLUGS = ['creditos', 'contabilidad', 'reparaciones', 'proformas', 'planilla'];

    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->json('dependencies')->nullable()->after('route');
            $table->string('required_permission')->nullable()->after('dependencies');
            $table->boolean('is_core')->default(false)->after('required_permission');
            $table->timestamp('activated_at')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('activated_at');
        });

        Schema::create('module_role', function (Blueprint $table) {
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['module_id', 'role_id']);
        });

        $catalog = [
            ['Inventario', 'inventario', 'Gestión de inventario y productos', '📦', 'inventario.index', [], 'inventario.view', false, 1],
            ['Compras', 'compras', 'Gestión de compras a proveedores', '🛒', 'compras.index', ['inventario', 'proveedores'], 'compras.view', false, 2],
            ['Ventas', 'ventas', 'Punto de venta y facturación', '💰', 'facturacion.pos', ['inventario', 'clientes'], 'ventas.view', false, 3],
            ['Clientes', 'clientes', 'Gestión de clientes', '👥', 'clientes.index', [], 'clientes.view', false, 4],
            ['Proveedores', 'proveedores', 'Gestión de proveedores', '🏭', 'proveedores.index', [], 'proveedores.view', false, 5],
            ['Caja', 'caja', 'Apertura, conteo y cierre de caja', '💵', 'arqueo.index', ['ventas'], 'caja.view', false, 6],
            ['Créditos', 'creditos', 'Créditos, abonos y estados de cuenta', '💳', 'creditos.index', ['ventas', 'clientes'], 'creditos.view', false, 7],
            ['Proformas', 'proformas', 'Cotizaciones y conversión a venta', '📄', 'proformas.index', ['ventas', 'clientes', 'inventario'], 'proformas.view', false, 8],
            ['Reparaciones', 'reparaciones', 'Órdenes y seguimiento de reparaciones', '🛠️', 'reparaciones.index', ['clientes', 'inventario'], 'reparaciones.view', false, 9],
            ['Planilla', 'planilla', 'Planilla y nómina', '🧑‍💼', 'planilla.index', [], 'planilla.view', false, 10],
            ['Contabilidad', 'contabilidad', 'Cuentas, asientos y reportes financieros', '📒', 'contabilidad.dashboard', ['ventas', 'compras'], 'contabilidad.view', false, 11],
            ['Reportes', 'reportes', 'Reportes operativos y exportaciones', '📊', 'reportes.index', ['ventas', 'compras', 'inventario'], 'reportes.view', false, 12],
            ['Configuración', 'configuracion', 'Administración del sistema', '⚙️', 'settings.index', [], 'configuracion.view', true, 13],
        ];

        foreach ($catalog as [$name, $slug, $description, $icon, $route, $dependencies, $permission, $core, $order]) {
            $existing = DB::table('modules')->where('slug', $slug)->first();
            $values = [
                'name' => $name, 'description' => $description, 'icon' => $icon, 'route' => $route,
                'dependencies' => json_encode($dependencies), 'required_permission' => $permission,
                'is_core' => $core, 'sort_order' => $order, 'updated_at' => now(),
            ];
            if ($existing) {
                DB::table('modules')->where('id', $existing->id)->update($values + [
                    'activated_at' => $existing->is_active ? ($existing->created_at ?? now()) : null,
                    'deactivated_at' => $existing->is_active ? null : now(),
                ]);
            } else {
                DB::table('modules')->insert($values + [
                    'slug' => $slug, 'is_active' => true, 'activated_at' => now(), 'deactivated_at' => null, 'created_at' => now(),
                ]);
            }
        }

        $roles = DB::table('roles')->get(['id', 'slug']);
        foreach (DB::table('modules')->get(['id', 'required_permission']) as $module) {
            foreach ($roles as $role) {
                $hasRequiredPermission = $module->required_permission && DB::table('permission_role')
                    ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                    ->where('permission_role.role_id', $role->id)
                    ->where('permissions.slug', $module->required_permission)
                    ->exists();

                if ($role->slug === 'admin' || $hasRequiredPermission) {
                    DB::table('module_role')->insertOrIgnore(['module_id' => $module->id, 'role_id' => $role->id]);
                }
            }
        }

        DB::table('permissions')->updateOrInsert(['slug' => 'configuracion.manage_modules'], [
            'name' => 'Configuracion Manage modules', 'module' => 'configuracion', 'action' => 'manage_modules',
            'description' => 'Administrar activación, dependencias y acceso a módulos', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'configuracion.manage_modules')->value('id');
        if ($adminId && $permissionId) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $adminId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'configuracion.manage_modules')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        DB::table('modules')->whereIn('slug', self::ADDED_SLUGS)->delete();
        Schema::dropIfExists('module_role');
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['dependencies', 'required_permission', 'is_core', 'activated_at', 'deactivated_at']);
        });
    }
};

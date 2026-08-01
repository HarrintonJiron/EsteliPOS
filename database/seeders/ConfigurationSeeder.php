<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Module;
use App\Models\Setting;
use App\Models\NumberSequence;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Crear módulos del sistema
        $modules = [
            ['name' => 'Inventario', 'slug' => 'inventario', 'description' => 'Gestión de inventario y productos', 'icon' => '📦', 'route' => 'inventario.index', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Compras', 'slug' => 'compras', 'description' => 'Gestión de compras a proveedores', 'icon' => '🛒', 'route' => 'compras.index', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Ventas', 'slug' => 'ventas', 'description' => 'Punto de venta y facturación', 'icon' => '💰', 'route' => 'facturacion.pos', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Clientes', 'slug' => 'clientes', 'description' => 'Gestión de clientes', 'icon' => '👥', 'route' => 'clientes.index', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Proveedores', 'slug' => 'proveedores', 'description' => 'Gestión de proveedores', 'icon' => '🏭', 'route' => 'proveedores.index', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Caja', 'slug' => 'caja', 'description' => 'Arqueo de caja', 'icon' => '💵', 'route' => 'arqueo.index', 'is_active' => true, 'sort_order' => 6],
            ['name' => 'Reportes', 'slug' => 'reportes', 'description' => 'Reportes y estadísticas', 'icon' => '📊', 'route' => 'reportes.index', 'is_active' => true, 'sort_order' => 7],
            ['name' => 'Contabilidad', 'slug' => 'contabilidad', 'description' => 'Contabilidad, cuentas y asientos', 'icon' => '📒', 'route' => 'contabilidad.cuentas.index', 'is_active' => true, 'sort_order' => 8],
            ['name' => 'Configuración', 'slug' => 'configuracion', 'description' => 'Configuración del sistema', 'icon' => '⚙️', 'route' => 'settings.index', 'is_active' => true, 'sort_order' => 9],
            ['name' => 'Créditos', 'slug' => 'creditos', 'description' => 'Créditos y abonos', 'icon' => '💳', 'route' => 'creditos.index', 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Proformas', 'slug' => 'proformas', 'description' => 'Cotizaciones', 'icon' => '📄', 'route' => 'proformas.index', 'is_active' => true, 'sort_order' => 11],
            ['name' => 'Reparaciones', 'slug' => 'reparaciones', 'description' => 'Órdenes de reparación', 'icon' => '🛠️', 'route' => 'reparaciones.index', 'is_active' => true, 'sort_order' => 12],
            ['name' => 'Planilla', 'slug' => 'planilla', 'description' => 'Planilla y nómina', 'icon' => '🧑‍💼', 'route' => 'planilla.index', 'is_active' => true, 'sort_order' => 13],
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(['slug' => $module['slug']], $module);
        }

        // Crear permisos por módulo
        $modulePermissions = [
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
            'configuracion' => ['view', 'edit', 'manage_users', 'manage_roles', 'manage_permissions', 'manage_modules'],
        ];

        foreach ($modulePermissions as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => ucfirst($module) . ' ' . ucfirst($action),
                        'slug' => $slug,
                        'module' => $module,
                        'action' => $action,
                        'description' => "Permiso para {$action} en {$module}",
                    ]
                );
            }
        }

        // Crear roles del sistema
        $roles = [
            ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Acceso total al sistema', 'is_system' => true],
            ['name' => 'Cajero', 'slug' => 'cajero', 'description' => 'Punto de venta y caja', 'is_system' => true],
            ['name' => 'Vendedor', 'slug' => 'vendedor', 'description' => 'Ventas y cotizaciones', 'is_system' => true],
            ['name' => 'Bodega', 'slug' => 'bodega', 'description' => 'Gestión de inventario', 'is_system' => true],
            ['name' => 'Compras', 'slug' => 'compras', 'description' => 'Gestión de compras', 'is_system' => true],
            ['name' => 'Contabilidad', 'slug' => 'contabilidad', 'description' => 'Reportes y finanzas', 'is_system' => true],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Supervisión general', 'is_system' => true],
            ['name' => 'Usuario básico', 'slug' => 'user', 'description' => 'Acceso básico sin privilegios administrativos', 'is_system' => true],
        ];

        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(['slug' => $roleData['slug']], $roleData);
        }

        // Asignar todos los permisos al rol administrador
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::all()->pluck('id');
            $adminRole->permissions()->sync($allPermissions);
        }

        // Asignar los permisos de contabilidad al rol contabilidad
        $contabilidadRole = Role::where('slug', 'contabilidad')->first();
        if ($contabilidadRole) {
            $contabilidadPermissions = Permission::where('module', 'contabilidad')->pluck('id');
            $contabilidadRole->permissions()->sync($contabilidadPermissions);
        }

        // Crear numeraciones iniciales
        $sequences = [
            ['type' => 'factura', 'prefix' => 'FAC-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
            ['type' => 'compra', 'prefix' => 'COM-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
            ['type' => 'cotizacion', 'prefix' => 'COT-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
            ['type' => 'recibo', 'prefix' => 'REC-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
            ['type' => 'ajuste', 'prefix' => 'AJU-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
            ['type' => 'asiento', 'prefix' => 'POL-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
        ];

        foreach ($sequences as $sequence) {
            NumberSequence::firstOrCreate(['type' => $sequence['type']], $sequence);
        }

        // Crear configuraciones generales por defecto
        $generalSettings = [
            ['key' => 'company_name', 'value' => 'Mi Agroservicio', 'type' => 'string', 'group' => 'general', 'description' => 'Nombre de la empresa'],
            ['key' => 'company_address', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Dirección de la empresa'],
            ['key' => 'company_phone', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Teléfono de la empresa'],
            ['key' => 'company_email', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Email de la empresa'],
            ['key' => 'company_ruc', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'RUC de la empresa'],
            ['key' => 'company_legal_name', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Razón social'],
            ['key' => 'company_city', 'value' => 'Estelí', 'type' => 'string', 'group' => 'general', 'description' => 'Ciudad'],
            ['key' => 'company_country', 'value' => 'Nicaragua', 'type' => 'string', 'group' => 'general', 'description' => 'País'],
            ['key' => 'currency', 'value' => 'NIO', 'type' => 'string', 'group' => 'general', 'description' => 'Moneda predeterminada'],
            ['key' => 'currency_symbol', 'value' => 'C$', 'type' => 'string', 'group' => 'general', 'description' => 'Símbolo monetario'],
            ['key' => 'tax_rate', 'value' => '15', 'type' => 'float', 'group' => 'general', 'description' => 'Porcentaje de IVA'],
            ['key' => 'timezone', 'value' => 'America/Managua', 'type' => 'string', 'group' => 'general', 'description' => 'Zona horaria'],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'type' => 'string', 'group' => 'general', 'description' => 'Formato de fecha'],
            ['key' => 'language', 'value' => 'es', 'type' => 'string', 'group' => 'general', 'description' => 'Idioma'],
            ['key' => 'company_logo', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Logo principal'],
            ['key' => 'ticket_logo', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Logo para tickets'],
            ['key' => 'invoice_footer', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Pie de factura'],
            ['key' => 'receipt_message', 'value' => '¡Gracias por su compra!', 'type' => 'string', 'group' => 'general', 'description' => 'Mensaje para recibos'],
        ];

        foreach ($generalSettings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Crear configuraciones de seguridad por defecto
        $securitySettings = [
            ['key' => 'session_timeout', 'value' => '60', 'type' => 'integer', 'group' => 'security', 'description' => 'Tiempo de sesión en minutos'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security', 'description' => 'Intentos máximos de login'],
            ['key' => 'password_min_length', 'value' => '8', 'type' => 'integer', 'group' => 'security', 'description' => 'Longitud mínima de contraseña'],
            ['key' => 'password_require_uppercase', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Requerir mayúsculas en contraseña'],
            ['key' => 'password_require_lowercase', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Requerir minúsculas en contraseña'],
            ['key' => 'password_require_numbers', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Requerir números en contraseña'],
            ['key' => 'password_require_special_chars', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Requerir caracteres especiales'],
            ['key' => 'two_factor_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Habilitar autenticación de dos factores'],
        ];

        foreach ($securitySettings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Crear configuraciones de apariencia por defecto
        $appearanceSettings = [
            ['key' => 'theme', 'value' => 'light', 'type' => 'string', 'group' => 'appearance', 'description' => 'Tema de la interfaz'],
            ['key' => 'primary_color', 'value' => '#6366f1', 'type' => 'string', 'group' => 'appearance', 'description' => 'Color principal'],
            ['key' => 'system_name', 'value' => 'Agroservicio POS', 'type' => 'string', 'group' => 'appearance', 'description' => 'Nombre del sistema'],
        ];

        foreach ($appearanceSettings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Catálogo de cuentas contables por defecto
        $this->call(ChartOfAccountsSeeder::class);
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;

class SettingsDashboardService
{
    public function build(): array
    {
        $users = User::query();
        $modules = Module::query();
        $general = Setting::getByGroup('general');
        $appearance = Setting::getByGroup('appearance');
        $duplicateRoleSlugs = Role::query()
            ->select('slug')
            ->whereNotNull('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $companyRequired = [
            'company_name',
            'company_legal_name',
            'company_ruc',
            'company_phone',
            'company_email',
            'company_address',
            'company_city',
            'company_country',
            'company_logo',
        ];
        $companyCompleted = collect($companyRequired)
            ->filter(fn (string $key): bool => filled($general[$key] ?? null))
            ->count();
        $activeModules = (clone $modules)->active()->count();
        $totalModules = (clone $modules)->count();
        $activeUsers = (clone $users)->where('is_active', true)->count();
        $totalUsers = (clone $users)->count();
        $activeTaxes = Tax::query()->where('is_active', true)->count();
        $user = auth()->user();
        $canManageUsers = $user?->isAdmin() || $user?->hasPermission('configuracion.manage_users');
        $canManageRoles = $user?->isAdmin() || $user?->hasPermission('configuracion.manage_roles');
        $canManagePermissions = $user?->isAdmin() || $user?->hasPermission('configuracion.manage_permissions');
        $canManageModules = $user?->isAdmin() || $user?->hasPermission('configuracion.manage_modules');

        $sections = [
            $this->section('general', 'general', '⚙️', 'Configuración general', 'Moneda, zona horaria e información básica.', 'Disponible', 'success', '8 opciones', route('settings.general'), 'Administrar'),
            $this->section('company', 'general', '🏢', 'Empresa', 'Identidad legal, contacto y documentos comerciales.', 'Disponible', $companyCompleted === count($companyRequired) ? 'success' : 'warning', "{$companyCompleted}/".count($companyRequired).' datos', route('settings.general'), 'Revisar datos', $companyCompleted < count($companyRequired)),
            $this->section('users', 'access', '👤', 'Usuarios', 'Cuentas, estado, roles y acceso al sistema.', $canManageUsers ? 'Disponible' : 'Acceso restringido', $canManageUsers ? 'success' : 'neutral', "{$activeUsers}/{$totalUsers} activos", $canManageUsers ? route('settings.users') : null, $canManageUsers ? 'Gestionar' : 'Sin permiso'),
            $this->section('roles', 'access', '🛡️', 'Roles', 'Perfiles de acceso y permisos agrupados.', $duplicateRoleSlugs > 0 ? 'Requiere saneamiento' : ($canManageRoles ? 'Disponible' : 'Acceso restringido'), $duplicateRoleSlugs > 0 ? 'danger' : ($canManageRoles ? 'success' : 'neutral'), $duplicateRoleSlugs > 0 ? "{$duplicateRoleSlugs} slugs duplicados" : Role::count().' roles', $canManageRoles ? route('settings.roles') : null, $canManageRoles ? 'Gestionar' : 'Sin permiso', $duplicateRoleSlugs > 0),
            $this->section('permissions', 'access', '🔐', 'Permisos', 'Matriz granular por módulo y acción.', $canManagePermissions ? 'Disponible' : 'Acceso restringido', $canManagePermissions ? 'success' : 'neutral', Permission::count().' permisos definidos', $canManagePermissions ? route('settings.permissions') : null, $canManagePermissions ? 'Ver matriz' : 'Sin permiso'),
            $this->section('modules', 'system', '🧩', 'Módulos', 'Disponibilidad, dependencias y acceso por roles.', $canManageModules ? 'Disponible' : 'Acceso restringido', $canManageModules ? 'success' : 'neutral', "{$activeModules}/{$totalModules} activos", $canManageModules ? route('settings.modules') : null, $canManageModules ? 'Administrar' : 'Sin permiso'),
            $this->comingSoon('sequences', 'operations', '🔢', 'Numeraciones', 'Consecutivos de documentos y comprobantes.'),
            $this->section('appearance', 'general', '🎨', 'Apariencia', 'Tema, color principal y nombre del sistema.', 'Disponible', 'success', ucfirst($appearance['theme'] ?? 'light'), route('settings.appearance'), 'Personalizar'),
            $this->comingSoon('security', 'system', '🔒', 'Seguridad', 'Sesiones, políticas avanzadas de contraseña y autenticación de dos factores.'),
            $this->section('taxes', 'operations', '🧾', 'Impuestos', 'Catálogo de tasas usado por ventas y compras.', 'Disponible', 'success', "{$activeTaxes} activos", route('settings.taxes.index'), 'Gestionar'),
            $this->comingSoon('sales', 'operations', '🛒', 'Caja y facturación', 'Reglas de venta, descuentos e impresión.'),
            $this->comingSoon('inventory', 'operations', '📦', 'Inventario', 'Stock, alertas, costos y ajustes.'),
            $this->comingSoon('backups', 'system', '💾', 'Respaldos', 'Copias, retención y recuperación.'),
            $this->comingSoon('audit', 'system', '📋', 'Auditoría', 'Consulta y trazabilidad completa de cambios administrativos.'),
            $this->comingSoon('diagnostics', 'system', '🩺', 'Diagnóstico del sistema', 'Estado técnico sin exponer información sensible.'),
        ];

        return [
            'stats' => [
                'active_users' => $activeUsers,
                'total_users' => $totalUsers,
                'active_modules' => $activeModules,
                'total_modules' => $totalModules,
                'configured_sections' => collect($sections)->whereNotNull('url')->count(),
                'total_sections' => count($sections),
                'attention' => $duplicateRoleSlugs + ($companyCompleted < count($companyRequired) ? 1 : 0),
            ],
            'categories' => [
                'all' => 'Todas',
                'general' => 'General',
                'access' => 'Acceso',
                'operations' => 'Operaciones',
                'system' => 'Sistema',
            ],
            'sections' => $sections,
            'recentActivity' => AuditLog::with('user')->latest()->limit(8)->get(),
        ];
    }

    private function section(
        string $id,
        string $category,
        string $icon,
        string $title,
        string $description,
        string $status,
        string $tone,
        string $metric,
        ?string $url = null,
        string $action = 'Próximamente',
        bool $attention = false,
    ): array {
        return compact('id', 'category', 'icon', 'title', 'description', 'status', 'tone', 'metric', 'url', 'action', 'attention');
    }

    private function comingSoon(
        string $id,
        string $category,
        string $icon,
        string $title,
        string $description,
    ): array {
        return $this->section(
            $id,
            $category,
            $icon,
            $title,
            $description,
            'Próximamente',
            'neutral',
            'Versión 2.0',
            null,
            'Versión 2.0',
        );
    }
}

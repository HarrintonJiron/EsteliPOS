<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ModuleAccessService
{
    public function canAccessSlug(string $slug, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $query = Module::query()->where('slug', $slug);
        if (Schema::hasTable('module_role')) {
            $query->with('roles:id');
        }

        $module = $query->first();
        if (! $module?->is_active) {
            return false;
        }

        // During an upgrade from the legacy module catalog, the pivot table
        // is not available yet. Keep active modules usable until migrations
        // establish the role-to-module access matrix.
        if (! Schema::hasTable('module_role')) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $userRoleIds = $user->roles()->pluck('roles.id');

        return $module->roles->pluck('id')->intersect($userRoleIds)->isNotEmpty();
    }

    public function accessibleSlugs(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $modules = Module::getActiveModules();
        if (! Schema::hasTable('module_role')) {
            return $modules->pluck('slug');
        }

        if ($user->isAdmin()) {
            return $modules->pluck('slug');
        }

        $roleIds = $user->roles()->pluck('roles.id');

        return $modules->filter(fn (Module $module) => $module->roles->pluck('id')->intersect($roleIds)->isNotEmpty())->pluck('slug')->values();
    }

    public function canViewDashboard(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermission('dashboard.view');
    }

    public function defaultHomeUrl(?User $user): string
    {
        if (! $user) {
            return route('login');
        }

        if ($this->canAccessSlug('caja', $user)) {
            return route('arqueo.index');
        }

        if ($this->canViewDashboard($user)) {
            return route('dashboard.general');
        }

        if ($this->canAccessSlug('ventas', $user)) {
            return route('facturacion.pos');
        }

        $firstModule = Module::getActiveModules()
            ->first(fn (Module $module) => $this->canAccessSlug($module->slug, $user) && filled($module->route));

        if ($firstModule && Route::has($firstModule->route)) {
            return route($firstModule->route);
        }

        return route('access.unavailable');
    }
}

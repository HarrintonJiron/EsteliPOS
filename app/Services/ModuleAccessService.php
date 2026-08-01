<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleAccessService
{
    public function canAccessSlug(string $slug, ?User $user): bool
    {
        if (! $user) return false;

        $module = Module::query()->with('roles:id')->where('slug', $slug)->first();
        if (! $module?->is_active) return false;
        if ($user->isAdmin()) return true;

        $userRoleIds = $user->roles()->pluck('roles.id');
        return $module->roles->pluck('id')->intersect($userRoleIds)->isNotEmpty();
    }

    public function accessibleSlugs(?User $user): Collection
    {
        if (! $user) return collect();

        $modules = Module::getActiveModules();
        if ($user->isAdmin()) return $modules->pluck('slug');

        $roleIds = $user->roles()->pluck('roles.id');
        return $modules->filter(fn (Module $module) => $module->roles->pluck('id')->intersect($roleIds)->isNotEmpty())->pluck('slug')->values();
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Module;
use DomainException;
use Illuminate\Support\Facades\DB;

class ModuleManagementService
{
    public function update(array $input): void
    {
        $modules = Module::with('roles')->ordered()->get();
        $desired = $modules->mapWithKeys(fn (Module $module) => [
            $module->slug => (bool) ($input[$module->id]['is_active'] ?? false),
        ]);

        foreach ($modules as $module) {
            if ($module->is_core && ! $desired[$module->slug]) {
                throw new DomainException("{$module->name} es un módulo núcleo y no puede desactivarse.");
            }
            if (! $desired[$module->slug]) continue;
            foreach ($module->dependencies ?? [] as $dependency) {
                if (! $desired->get($dependency, false)) {
                    $dependencyName = $modules->firstWhere('slug', $dependency)?->name ?? $dependency;
                    throw new DomainException("{$module->name} requiere que {$dependencyName} permanezca activo.");
                }
            }
        }

        DB::transaction(function () use ($modules, $input) {
            $old = $this->snapshot($modules);
            foreach ($modules as $module) {
                $row = $input[$module->id] ?? [];
                $active = (bool) ($row['is_active'] ?? false);
                $changed = $active !== $module->is_active;
                $module->update([
                    'is_active' => $active,
                    'sort_order' => (int) ($row['sort_order'] ?? $module->sort_order),
                    'activated_at' => $changed && $active ? now() : $module->activated_at,
                    'deactivated_at' => $changed && ! $active ? now() : ($active ? null : $module->deactivated_at),
                ]);
                $module->roles()->sync(array_values(array_unique($row['roles'] ?? [])));
            }
            Module::flushModuleCache();
            $fresh = Module::with('roles')->ordered()->get();
            AuditLog::log('modules.updated', 'Catálogo de módulos actualizado', null, $old, $this->snapshot($fresh));
        });
    }

    private function snapshot($modules): array
    {
        return $modules->mapWithKeys(fn (Module $module) => [$module->slug => [
            'is_active' => $module->is_active,
            'sort_order' => $module->sort_order,
            'roles' => $module->roles->pluck('slug')->sort()->values()->all(),
        ]])->all();
    }
}

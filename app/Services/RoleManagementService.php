<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RoleManagementService
{
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([...Arr::only($data, ['name', 'slug', 'description']), 'is_system' => false]);
            $role->permissions()->sync(array_unique($data['permissions'] ?? []));
            AuditLog::log('role.created', "Rol {$role->name} creado", $role, null, $this->snapshot($role));
            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $old = $this->snapshot($role);
            $attributes = $role->is_system
                ? Arr::only($data, ['description'])
                : Arr::only($data, ['name', 'slug', 'description']);
            $role->update($attributes);

            $permissionIds = array_unique($data['permissions'] ?? []);
            if ($role->slug === 'admin') {
                $permissionIds = Permission::pluck('id')->all();
            }
            $role->permissions()->sync($permissionIds);
            $role->refresh();
            AuditLog::log('role.updated', "Rol {$role->name} actualizado", $role, $old, $this->snapshot($role));
            return $role;
        });
    }

    public function clone(Role $source, array $data): Role
    {
        $data['permissions'] = $source->permissions()->pluck('permissions.id')->all();
        $clone = $this->create($data);
        AuditLog::log('role.cloned', "Rol {$source->name} clonado como {$clone->name}", $clone, ['source_role_id' => $source->id], $this->snapshot($clone));
        return $clone;
    }

    public function delete(Role $role, ?int $replacementRoleId): void
    {
        if ($role->is_system) {
            throw new DomainException('Los roles del sistema no se pueden eliminar.');
        }

        DB::transaction(function () use ($role, $replacementRoleId) {
            $role->load('users', 'permissions');
            $replacement = $replacementRoleId ? Role::find($replacementRoleId) : null;

            if ($role->users->isNotEmpty() && ! $replacement) {
                throw new DomainException('Selecciona un rol de reemplazo antes de eliminar un rol con usuarios asignados.');
            }

            $old = $this->snapshot($role) + ['user_ids' => $role->users->pluck('id')->all()];
            if ($replacement) {
                foreach ($role->users as $user) {
                    $user->roles()->syncWithoutDetaching([$replacement->id]);
                    $user->roles()->detach($role->id);
                    $user->update(['role' => $replacement->slug === 'admin' ? 'admin' : 'user']);
                }
            }

            AuditLog::log('role.deleted', "Rol {$role->name} eliminado", $role, $old, ['replacement_role_id' => $replacement?->id]);
            $role->delete();
        });
    }

    private function snapshot(Role $role): array
    {
        $role->loadMissing('permissions');
        return [
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions' => $role->permissions->pluck('slug')->sort()->values()->all(),
        ];
    }
}

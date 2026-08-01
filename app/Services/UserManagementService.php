<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserManagementService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roleIds = array_values(array_unique($data['roles'] ?? []));
            $permissionIds = array_values(array_unique($data['permissions'] ?? []));
            $photo = isset($data['profile_photo']) ? $this->storePhoto($data['profile_photo']) : null;

            $user = User::create([
                ...Arr::only($data, ['name', 'username', 'email', 'phone', 'password']),
                'profile_photo' => $photo,
                'is_active' => $data['is_active'] ?? true,
                'force_password_change' => $data['force_password_change'] ?? true,
                'role' => $this->legacyRole($roleIds),
                'password_changed_at' => ($data['force_password_change'] ?? true) ? null : now(),
            ]);

            $user->roles()->sync($roleIds);
            $user->directPermissions()->sync($permissionIds);

            AuditLog::log('user.created', "Usuario {$user->name} creado", $user, null, $this->auditSnapshot($user));

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $old = $this->auditSnapshot($user);
            $roleIds = array_values(array_unique($data['roles'] ?? []));
            $willRemainAdmin = $this->legacyRole($roleIds) === 'admin';

            if ($user->is_active && $user->isAdmin() && ! $willRemainAdmin) {
                $this->guardLastAdministrator($user);
            }

            $photo = $user->profile_photo;
            if (! empty($data['remove_profile_photo'])) {
                $this->deletePhoto($photo);
                $photo = null;
            }
            if (isset($data['profile_photo'])) {
                $newPhoto = $this->storePhoto($data['profile_photo']);
                $this->deletePhoto($photo);
                $photo = $newPhoto;
            }

            $user->update([
                ...Arr::only($data, ['name', 'username', 'email', 'phone']),
                'profile_photo' => $photo,
                'role' => $willRemainAdmin ? 'admin' : 'user',
            ]);
            $user->roles()->sync($roleIds);
            $user->directPermissions()->sync(array_values(array_unique($data['permissions'] ?? [])));
            $user->refresh();

            AuditLog::log('user.updated', "Usuario {$user->name} actualizado", $user, $old, $this->auditSnapshot($user));

            return $user;
        });
    }

    public function toggleActive(User $user, User $actor): User
    {
        if ($user->is($actor)) {
            throw new DomainException('No puedes desactivar tu propio usuario.');
        }
        if ($user->is_active && $user->isAdmin()) {
            $this->guardLastAdministrator($user);
        }

        $old = ['is_active' => $user->is_active];
        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::log('user.status_changed', "Usuario {$user->name} ".($user->is_active ? 'activado' : 'desactivado'), $user, $old, ['is_active' => $user->is_active]);

        return $user;
    }

    public function resetPassword(User $user, string $password, bool $forceChange = true): void
    {
        $user->update([
            'password' => $password,
            'force_password_change' => $forceChange,
            'password_changed_at' => $forceChange ? null : now(),
        ]);

        AuditLog::log('user.password_reset', "Contraseña de {$user->name} restablecida", $user, null, ['force_password_change' => $forceChange]);
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new DomainException('No puedes eliminar tu propio usuario.');
        }
        if ($user->is_active && $user->isAdmin()) {
            $this->guardLastAdministrator($user);
        }
        if ($this->hasOperationalHistory($user)) {
            throw new DomainException('Este usuario tiene historial operativo y no puede eliminarse. Desactívalo para conservar la trazabilidad.');
        }

        DB::transaction(function () use ($user) {
            AuditLog::log('user.deleted', "Usuario {$user->name} eliminado", $user, $this->auditSnapshot($user), null);
            $photo = $user->profile_photo;
            $user->delete();
            $this->deletePhoto($photo);
        });
    }

    private function guardLastAdministrator(User $user): void
    {
        $otherActiveAdmins = User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'admin'))
            ->exists();

        if (! $otherActiveAdmins) {
            throw new DomainException('Debe permanecer al menos un administrador activo en el sistema.');
        }
    }

    private function legacyRole(array $roleIds): string
    {
        return Role::query()->whereIn('id', $roleIds)->where('slug', 'admin')->exists() ? 'admin' : 'user';
    }

    private function hasOperationalHistory(User $user): bool
    {
        foreach (['sales', 'purchases', 'inventory_movements', 'inventory_adjustments', 'credit_payments', 'journal_entries', 'arqueos', 'proformas', 'repair_orders'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id') && DB::table($table)->where('user_id', $user->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $photo->store('users', 'public');
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'users/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function auditSnapshot(User $user): array
    {
        $user->loadMissing('roles', 'directPermissions');

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('slug')->sort()->values()->all(),
            'direct_permissions' => $user->directPermissions->pluck('slug')->sort()->values()->all(),
        ];
    }
}

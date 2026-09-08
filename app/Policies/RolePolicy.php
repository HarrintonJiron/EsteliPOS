<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool { return $user->hasPermission('configuracion.view'); }
    public function view(User $user, Role $role): bool { return $user->hasPermission('configuracion.view'); }
    public function create(User $user): bool { return $user->hasPermission('configuracion.manage_roles'); }
    public function update(User $user, Role $role): bool { return $user->hasPermission('configuracion.manage_roles'); }
    public function delete(User $user, Role $role): bool { return ! $role->is_system && $user->hasPermission('configuracion.manage_roles'); }
}

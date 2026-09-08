<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->hasPermission('configuracion.manage_users'); }
    public function view(User $user, User $target): bool { return $user->hasPermission('configuracion.manage_users'); }
    public function create(User $user): bool { return $user->hasPermission('configuracion.manage_users'); }
    public function update(User $user, User $target): bool { return $user->hasPermission('configuracion.manage_users'); }
    public function delete(User $user, User $target): bool { return $user->hasPermission('configuracion.manage_users'); }
}

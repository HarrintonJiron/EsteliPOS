<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->hasPermission('configuracion.manage_modules'); }
    public function update(User $user, Module $module): bool { return $user->hasPermission('configuracion.manage_modules'); }
}

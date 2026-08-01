<?php

namespace App\Policies;

use App\Models\FiscalPeriod;
use App\Models\User;

class FiscalPeriodPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool { return $user->hasPermission('contabilidad.view'); }
    public function close(User $user, FiscalPeriod $period): bool { return $user->hasPermission('contabilidad.close_period'); }
    public function reopen(User $user, FiscalPeriod $period): bool { return $user->hasPermission('contabilidad.close_period'); }
}

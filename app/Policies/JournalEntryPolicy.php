<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool { return $user->hasPermission('contabilidad.view'); }
    public function view(User $user, JournalEntry $entry): bool { return $user->hasPermission('contabilidad.view'); }
    public function create(User $user): bool { return $user->hasPermission('contabilidad.create'); }
    public function post(User $user, JournalEntry $entry): bool { return $user->hasPermission('contabilidad.edit'); }
    public function void(User $user, JournalEntry $entry): bool { return $user->hasPermission('contabilidad.edit'); }
    public function delete(User $user, JournalEntry $entry): bool { return $user->hasPermission('contabilidad.delete'); }
}

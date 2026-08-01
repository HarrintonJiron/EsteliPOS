<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'is_active',
        'profile_photo',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles()
    {
        if (! Schema::hasTable('role_user')) {
            return collect();
        }

        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions()
    {
        if (! Schema::hasTable('role_user') || ! Schema::hasTable('permission_role')) {
            return collect();
        }

        return $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function hasRole(string $roleSlug): bool
    {
        $legacyRole = (string) ($this->role ?? '');
        if ($this->matchesLegacyRole($legacyRole, $roleSlug)) {
            return true;
        }

        if (! Schema::hasTable('role_user') || ! Schema::hasTable('roles')) {
            return false;
        }

        $query = $this->roles();

        if (Schema::hasColumn('roles', 'slug')) {
            return $query->where('slug', $roleSlug)->exists();
        }

        $roleName = $this->normalizeRoleName($roleSlug);

        return $query->where(function ($subQuery) use ($roleName): void {
            $subQuery->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(name, " ", ""), "_", ""), "-", "")) = ?', [$roleName]);
        })->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if (! Schema::hasTable('role_user') || ! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return false;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
    }

    public function hasAnyPermission(array $permissionSlugs): bool
    {
        if (! Schema::hasTable('role_user') || ! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return false;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permissionSlugs) {
            $query->whereIn('slug', $permissionSlugs);
        })->exists();
    }

    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (! $this->hasPermission($slug)) {
                return false;
            }
        }

        return true;
    }

    public function assignRole(string $roleSlug)
    {
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            $role = $this->findRole($roleSlug);
            if ($role && ! $this->hasRole($roleSlug)) {
                $this->roles()->attach($role->id);
            }
        }

        $this->forceFill(['role' => $roleSlug])->save();
    }

    public function removeRole(string $roleSlug)
    {
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            $role = $this->findRole($roleSlug);
            if ($role) {
                $this->roles()->detach($role->id);
            }
        }

        $this->forceFill(['role' => 'user'])->save();
    }

    public function syncRoles(array $roleSlugs)
    {
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            $roleIds = $this->findRoles($roleSlugs)->pluck('id');
            $this->roles()->sync($roleIds);
        }

        if (! empty($roleSlugs)) {
            $this->forceFill(['role' => $roleSlugs[0]])->save();
        }
    }

    private function matchesLegacyRole(string $legacyRole, string $roleSlug): bool
    {
        if ($legacyRole === '') {
            return false;
        }

        $legacyRoleNormalized = $this->normalizeRoleName($legacyRole);
        $roleSlugNormalized = $this->normalizeRoleName($roleSlug);

        if ($legacyRoleNormalized === $roleSlugNormalized) {
            return true;
        }

        return ($legacyRoleNormalized === 'administrator' && $roleSlugNormalized === 'admin')
            || ($legacyRoleNormalized === 'admin' && $roleSlugNormalized === 'administrator');
    }

    private function normalizeRoleName(string $value): string
    {
        return strtolower(str_replace([' ', '_', '-'], '', $value));
    }

    private function findRole(string $roleSlug): ?Role
    {
        if (! Schema::hasTable('roles')) {
            return null;
        }

        $query = Role::query();

        if (Schema::hasColumn('roles', 'slug')) {
            return $query->where('slug', $roleSlug)->first();
        }

        $roleName = $this->roleNameFromSlug($roleSlug);

        if ($roleName) {
            return $query->where('name', $roleName)->first();
        }

        return null;
    }

    private function findRoles(array $roleSlugs): \Illuminate\Database\Eloquent\Collection
    {
        if (! Schema::hasTable('roles')) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $query = Role::query();

        if (Schema::hasColumn('roles', 'slug')) {
            return $query->whereIn('slug', $roleSlugs)->get();
        }

        $names = array_values(array_filter(array_map(fn ($roleSlug) => $this->roleNameFromSlug($roleSlug), $roleSlugs)));

        return $query->whereIn('name', $names)->get();
    }

    private function roleNameFromSlug(string $roleSlug): ?string
    {
        $normalized = $this->normalizeRoleName($roleSlug);

        return match ($normalized) {
            'admin', 'administrator' => 'Administrador',
            'vendedor' => 'Vendedor',
            'contable' => 'Contable',
            'cajero' => 'Cajero',
            'bodega' => 'Bodega',
            'compras' => 'Compras',
            'contabilidad' => 'Contabilidad',
            'supervisor' => 'Supervisor',
            default => null,
        };
    }

    // Legacy methods for backward compatibility
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->hasRole('user') || $this->role === 'user';
    }

    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }
}

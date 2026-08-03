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
        'username',
        'email',
        'phone',
        'role',
        'password',
        'is_active',
        'profile_photo',
        'last_login_at',
        'force_password_change',
        'password_changed_at',
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
            'force_password_change' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions()
    {
        $rolePermissions = collect();
        if (Schema::hasTable('role_user') && Schema::hasTable('permission_role') && Schema::hasTable('roles')) {
            $rolePermissions = $this->roles()->with('permissions')->get()->pluck('permissions')->flatten();
        }

        $directPermissions = Schema::hasTable('permission_user')
            ? $this->directPermissions()->get()
            : collect();

        return $rolePermissions->merge($directPermissions)->unique('id')->values();
    }

    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function operationalExpenses()
    {
        return $this->hasMany(OperationalExpense::class);
    }

    public function hasRole(string $roleSlug): bool
    {
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'slug') && Schema::hasTable('role_user')) {
            return $this->roles()->where('slug', $roleSlug)->exists();
        }

        return Schema::hasColumn($this->getTable(), 'role')
            ? (string) $this->role === $roleSlug
            : false;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $hasDirect = Schema::hasTable('permission_user') && Schema::hasTable('permissions')
            ? $this->directPermissions()->where('slug', $permissionSlug)->exists()
            : false;

        $hasThroughRole = Schema::hasTable('role_user')
            && Schema::hasTable('permission_role')
            && Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            ? $this->roles()->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })->exists()
            : false;

        return $hasDirect || $hasThroughRole;
    }

    public function hasAnyPermission(array $permissionSlugs): bool
    {
        $hasDirect = Schema::hasTable('permission_user') && Schema::hasTable('permissions')
            ? $this->directPermissions()->whereIn('slug', $permissionSlugs)->exists()
            : false;

        $hasThroughRole = Schema::hasTable('role_user')
            && Schema::hasTable('permission_role')
            && Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            ? $this->roles()->whereHas('permissions', function ($query) use ($permissionSlugs) {
                $query->whereIn('slug', $permissionSlugs);
            })->exists()
            : false;

        return $hasDirect || $hasThroughRole;
    }

    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    public function assignRole(string $roleSlug)
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role && !$this->hasRole($roleSlug)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole(string $roleSlug)
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function syncRoles(array $roleSlugs)
    {
        $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id');
        $this->roles()->sync($roleIds);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }
}

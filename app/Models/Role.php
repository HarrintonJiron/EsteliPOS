<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'module_role');
    }

    public function hasPermission($permissionSlug)
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return false;
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function givePermission($permissionSlug)
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return;
        }

        $permission = Permission::where('slug', $permissionSlug)->first();
        if ($permission && !$this->hasPermission($permissionSlug)) {
            $this->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function revokePermission($permissionSlug)
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return;
        }

        $permission = Permission::where('slug', $permissionSlug)->first();
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }

    public function syncPermissions(array $permissionSlugs)
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }
}

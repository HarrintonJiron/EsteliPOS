<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'route', 'dependencies', 'required_permission', 'is_core', 'is_active', 'activated_at', 'deactivated_at', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_core' => 'boolean',
        'dependencies' => 'array',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (Module $module): void {
            Cache::forget("modules.{$module->slug}.active");
            Cache::forget('modules.active');

            if ($module->wasChanged('slug')) {
                Cache::forget('modules.'.$module->getOriginal('slug').'.active');
            }
        });

        static::deleted(function (Module $module): void {
            Cache::forget("modules.{$module->slug}.active");
            Cache::forget('modules.active');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'module_role');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function isActive($slug)
    {
        return Cache::rememberForever("modules.{$slug}.active", function () use ($slug) {
            return static::where('slug', $slug)->where('is_active', true)->exists();
        });
    }

    public static function activate($slug)
    {
        $module = static::where('slug', $slug)->first();
        if ($module) {
            $module->update(['is_active' => true]);
        }
        return $module;
    }

    public static function deactivate($slug)
    {
        $module = static::where('slug', $slug)->first();
        if ($module) {
            $module->update(['is_active' => false]);
        }
        return $module;
    }

    public static function getActiveModules()
    {
        return Cache::rememberForever('modules.active', function () {
            return static::with('roles')->active()->ordered()->get();
        });
    }

    public static function flushModuleCache(): void
    {
        static::pluck('slug')->each(fn (string $slug) => Cache::forget("modules.{$slug}.active"));
        Cache::forget('modules.active');
    }
}

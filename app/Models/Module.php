<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'route', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
            Cache::forget("modules.{$slug}.active");
        }
        return $module;
    }

    public static function deactivate($slug)
    {
        $module = static::where('slug', $slug)->first();
        if ($module) {
            $module->update(['is_active' => false]);
            Cache::forget("modules.{$slug}.active");
        }
        return $module;
    }

    public static function getActiveModules()
    {
        return Cache::rememberForever('modules.active', function () {
            return static::active()->ordered()->get();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    protected $casts = [
        // No cast automático en value, manejamos conversión manualmente
    ];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }
            
            $value = $setting->value;
            
            return match($setting->type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $value,
                'float' => (float) $value,
                'json', 'array' => json_decode($value, true),
                default => $value,
            };
        });
    }

    public static function set($key, $value, $type = 'string', $group = 'general', $description = null)
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_bool($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );
        
        Cache::forget("settings.{$key}");
        
        return $setting;
    }

    public static function getByGroup($group)
    {
        return static::where('group', $group)->get()->mapWithKeys(function ($setting) {
            $value = $setting->value;
            return [
                $setting->key => match($setting->type) {
                    'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $value,
                    'float' => (float) $value,
                    'json', 'array' => json_decode($value, true),
                    default => $value,
                }
            ];
        })->toArray();
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }
}

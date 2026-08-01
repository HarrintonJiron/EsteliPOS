<?php

namespace App\Models;

use App\Support\SettingDefinition;
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
            
            return static::castValue($setting->value, $setting->type);
        });
    }

    public static function set($key, $value, $type = null, $group = 'general', $description = null)
    {
        $type ??= SettingDefinition::typeFor($key);

        $attributes = [
            'value' => static::serializeValue($value, $type),
            'type' => $type,
            'group' => $group,
        ];

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        $setting = static::updateOrCreate(['key' => $key], $attributes);
        
        Cache::forget("settings.{$key}");
        
        return $setting;
    }

    public static function getByGroup($group)
    {
        return static::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [
                $setting->key => static::castValue($setting->value, $setting->type),
            ];
        })->toArray();
    }

    public static function castValue(mixed $value, string $type): mixed
    {
        $value = static::decodeLegacyScalar($value);

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    private static function serializeValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'json', 'array' => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => $value,
        };
    }

    private static function decodeLegacyScalar(mixed $value): mixed
    {
        if (! is_string($value) || strlen($value) < 2 || $value[0] !== '"') {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_scalar($decoded)
            ? $decoded
            : $value;
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }
}

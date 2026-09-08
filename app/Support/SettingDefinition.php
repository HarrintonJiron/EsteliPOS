<?php

namespace App\Support;

final class SettingDefinition
{
    /** @var array<string, string> */
    private const TYPES = [
        'tax_rate' => 'float',
        'session_timeout' => 'integer',
        'max_login_attempts' => 'integer',
        'password_min_length' => 'integer',
        'password_require_uppercase' => 'boolean',
        'password_require_lowercase' => 'boolean',
        'password_require_numbers' => 'boolean',
        'password_require_special_chars' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    public static function typeFor(string $key, string $fallback = 'string'): string
    {
        return self::TYPES[$key] ?? $fallback;
    }
}

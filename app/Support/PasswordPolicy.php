<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    public static function rule(): Password
    {
        $rule = Password::min(max(6, (int) Setting::get('password_min_length', 8)));

        if (Setting::get('password_require_uppercase', false)) {
            $rule->mixedCase();
        }
        if (Setting::get('password_require_numbers', false)) {
            $rule->numbers();
        }
        if (Setting::get('password_require_special_chars', false)) {
            $rule->symbols();
        }

        return $rule;
    }

    public static function summary(): string
    {
        $parts = ['mínimo '.max(6, (int) Setting::get('password_min_length', 8)).' caracteres'];
        if (Setting::get('password_require_uppercase', false)) $parts[] = 'mayúsculas y minúsculas';
        if (Setting::get('password_require_numbers', false)) $parts[] = 'números';
        if (Setting::get('password_require_special_chars', false)) $parts[] = 'símbolos';

        return implode(', ', $parts);
    }
}

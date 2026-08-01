<?php

namespace App\Http\Requests;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() || $this->user()?->hasPermission('configuracion.manage_users'); }

    protected function prepareForValidation(): void
    {
        $this->merge(['force_password_change' => $this->boolean('force_password_change')]);
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
            'force_password_change' => ['boolean'],
        ];
    }
}

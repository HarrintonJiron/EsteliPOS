<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizeCreditOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->hasPermission('ventas.create');
    }

    public function rules(): array
    {
        return [
            'admin_login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}

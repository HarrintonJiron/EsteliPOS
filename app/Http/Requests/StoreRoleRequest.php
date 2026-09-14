<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() || $this->user()?->hasPermission('configuracion.manage_roles'); }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'unique:roles,name'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}

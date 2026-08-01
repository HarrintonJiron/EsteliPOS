<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() || $this->user()?->hasPermission('configuracion.manage_roles'); }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => $role->is_system
                ? ['required', Rule::in([$role->name])]
                : ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($role)],
            'slug' => $role->is_system
                ? ['required', Rule::in([$role->slug])]
                : ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'slug')->ignore($role)],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}

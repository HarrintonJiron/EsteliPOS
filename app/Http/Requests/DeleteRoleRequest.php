<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() || $this->user()?->hasPermission('configuracion.manage_roles'); }

    public function rules(): array
    {
        return [
            'replacement_role_id' => [
                'nullable', 'integer',
                Rule::exists('roles', 'id'),
                Rule::notIn([(int) $this->route('role')->id]),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->hasPermission('configuracion.manage_modules');
    }

    protected function prepareForValidation(): void
    {
        $modules = collect($this->input('modules', []))->map(function ($module) {
            $module['is_active'] = filter_var($module['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $module['roles'] = array_values($module['roles'] ?? []);
            return $module;
        })->all();
        $this->merge(['modules' => $modules]);
    }

    public function rules(): array
    {
        return [
            'modules' => ['required', 'array'],
            'modules.*.is_active' => ['required', 'boolean'],
            'modules.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'modules.*.roles' => ['array'],
            'modules.*.roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}

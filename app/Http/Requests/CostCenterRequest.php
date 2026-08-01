<?php

namespace App\Http\Requests;

use App\Models\CostCenter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $costCenterId = $this->route('centro_costo')?->id ?? $this->route('centro_costo');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('cost_centers', 'code')->ignore($costCenterId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(array_keys(CostCenter::TYPES))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

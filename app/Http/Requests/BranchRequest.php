<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($branch)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Branch::TYPES))],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', Rule::unique('branches', 'warehouse_id')->ignore($branch)],
            'price_list_id' => ['nullable', 'integer', 'exists:price_lists,id', Rule::unique('branches', 'price_list_id')->ignore($branch)],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

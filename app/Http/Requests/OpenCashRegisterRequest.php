<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'branch_id' => [
                Branch::query()->where('is_active', true)->count() > 1 ? 'required' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opening_amount.required' => 'Digite el monto de efectivo con el que abre la caja.',
            'opening_amount.numeric' => 'El monto inicial debe ser un número válido.',
            'opening_amount.min' => 'El monto inicial no puede ser negativo.',
            'opening_amount.max' => 'El monto inicial excede el límite permitido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('branch_id')) {
            $branchId = $this->user()?->branch_id
                ?? (Branch::query()->where('is_active', true)->count() === 1
                    ? Branch::query()->where('is_active', true)->value('id')
                    : null);
            if ($branchId) {
                $this->merge(['branch_id' => $branchId]);
            }
        }

        if (is_string($this->input('opening_amount'))) {
            $this->merge([
                'opening_amount' => str_replace(',', '', trim($this->input('opening_amount'))),
            ]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $assignedBranchId = $this->user()?->branch_id;
            if ($assignedBranchId && (int) $this->input('branch_id') !== (int) $assignedBranchId) {
                $validator->errors()->add('branch_id', 'Tu usuario solo puede abrir caja en su sucursal asignada.');
            }
        }];
    }
}

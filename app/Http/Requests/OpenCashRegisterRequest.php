<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
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
        if (is_string($this->input('opening_amount'))) {
            $this->merge([
                'opening_amount' => str_replace(',', '', trim($this->input('opening_amount'))),
            ]);
        }
    }
}

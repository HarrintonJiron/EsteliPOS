<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'status' => ['nullable', 'in:pending,completed,canceled'],
            'currency' => ['required', Rule::in(['NIO', 'USD', 'EUR'])],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Selecciona el proveedor.',
            'currency.required' => 'Selecciona la moneda de la compra.',
            'currency.in' => 'Moneda no permitida.',
            'exchange_rate.min' => 'El tipo de cambio debe ser mayor que cero.',
            'items.required' => 'Agrega al menos un producto.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'items.*.price.min' => 'El costo no puede ser negativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) ($this->input('currency') ?: 'NIO')),
        ]);
    }
}

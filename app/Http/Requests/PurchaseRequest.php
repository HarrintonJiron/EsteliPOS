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
            'payment_type' => ['nullable', 'in:cash,transfer,credit'],
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
            'payment_type.in' => 'Indica si la compra es de contado o a crédito.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $paymentType = $this->input('payment_type');
        $status = $this->input('status');

        if ($paymentType === 'credit') {
            $status = 'pending';
        } elseif (in_array($paymentType, ['cash', 'transfer'], true) && $status !== 'canceled') {
            $status = 'completed';
        } elseif ($status === 'pending') {
            $paymentType = 'credit';
        } elseif ($status === 'completed' && ! in_array($paymentType, ['cash', 'transfer'], true)) {
            $paymentType = 'cash';
        }

        $this->merge([
            'currency' => strtoupper((string) ($this->input('currency') ?: 'NIO')),
            'status' => $status,
            'payment_type' => $paymentType,
        ]);
    }
}

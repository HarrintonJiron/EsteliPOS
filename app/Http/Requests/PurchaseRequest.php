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
            'purchase_mode' => ['nullable', 'in:immediate,proforma'],
            'status' => ['nullable', 'in:ordered,pending,completed,canceled'],
            'payment_type' => ['nullable', 'in:cash,transfer,credit'],
            'currency' => ['required', Rule::in(['NIO', 'USD', 'EUR'])],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
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
            'items.max' => 'Una compra no puede contener más de 250 líneas.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe o está inactivo.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'items.*.quantity.max' => 'La cantidad excede el máximo permitido.',
            'items.*.price.min' => 'El costo no puede ser negativo.',
            'items.*.price.max' => 'El costo excede el máximo permitido.',
            'payment_type.in' => 'Indica si la compra es de contado o a crédito.',
            'purchase_mode.in' => 'Selecciona compra inmediata o pedido en proceso.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $paymentType = $this->input('payment_type');
        $status = $this->input('status');
        $purchaseMode = $this->input('purchase_mode');

        if ($purchaseMode === 'proforma' || $status === 'ordered') {
            $purchaseMode = 'proforma';
            $status = 'ordered';
            $paymentType = in_array($paymentType, ['cash', 'transfer', 'credit'], true)
                ? $paymentType
                : 'credit';
        } elseif ($paymentType === 'credit') {
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
            'purchase_mode' => $purchaseMode ?: 'immediate',
            'status' => $status,
            'payment_type' => $paymentType,
        ]);
    }
}

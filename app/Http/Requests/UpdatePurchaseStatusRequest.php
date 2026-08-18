<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseStatusRequest extends FormRequest
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
            'status' => ['required', 'in:completed,canceled'],
            'payment_type' => ['nullable', 'required_if:status,completed', 'in:cash,transfer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Indica si vas a pagar o anular la compra.',
            'status.in' => 'Acción no válida.',
            'payment_type.required_if' => 'Indica si el pago es en efectivo o transferencia.',
            'payment_type.in' => 'El pago solo puede ser en efectivo o transferencia.',
        ];
    }
}

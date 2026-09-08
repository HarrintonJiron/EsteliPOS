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
            'status' => ['required', 'in:received,completed,canceled'],
            'payment_type' => ['nullable', 'required_if:status,received,completed', 'in:cash,transfer,credit'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Indica si vas a recibir, pagar o anular la compra.',
            'status.in' => 'Acción no válida.',
            'payment_type.required_if' => 'Indica si la compra se recibe a crédito, en efectivo o por transferencia.',
            'payment_type.in' => 'La forma de pago seleccionada no es válida.',
        ];
    }
}

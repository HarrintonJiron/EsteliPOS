<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'ruc' => 'nullable|string|max:30',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'type' => 'nullable|string|max:255',
            'payment_condition' => 'nullable|in:contado,credito_15,credito_30,credito_60',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}

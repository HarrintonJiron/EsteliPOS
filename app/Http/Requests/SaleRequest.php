<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            // 'user_id' is set server-side from the authenticated user
            'invoice_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('sales', 'invoice_number')->ignore($this->route('id')),
            ],
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'payment_type' => 'required|in:cash,transfer,credit',
            'tax_included' => 'required|boolean',
            'billing_name' => 'required|string|max:255',
            'billing_business_name' => 'nullable|string|max:255',
            'billing_document_type' => 'nullable|in:cedula,ruc',
            'billing_ruc' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^(?:[A-Za-z]\d{13}|\d{3}-\d{6}-\d{4}[A-Za-z0-9]|\d{14}[A-Za-z0-9])$/',
            ],
            'billing_phone' => 'nullable|string|max:50',
            'billing_email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
}

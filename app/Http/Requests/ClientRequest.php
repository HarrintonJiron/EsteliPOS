<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('id');

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'code')->ignore($clientId),
            ],
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'ruc' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9]{3}-[0-9]{6}-[0-9]{4}[A-Za-z0-9]$/',
            ],
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'credit_enabled' => 'nullable|boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:1|max:365',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'credit_enabled' => $this->boolean('credit_enabled'),
            'credit_limit' => $this->input('credit_limit', 0),
            'credit_days' => $this->input('credit_days', 30),
        ]);
    }
}

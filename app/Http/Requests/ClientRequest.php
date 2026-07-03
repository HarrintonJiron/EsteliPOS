<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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
            'ruc' => [
                'nullable',
                'string',
                'max:30',
                // formato flexible (con guiones) típico en Nicaragua: 001-######-####X
                'regex:/^[0-9]{3}-[0-9]{6}-[0-9]{4}[A-Za-z0-9]$/',
            ],
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isActive() && (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remove_company_logo' => $this->boolean('remove_company_logo'),
            'remove_ticket_logo' => $this->boolean('remove_ticket_logo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_legal_name' => ['nullable', 'string', 'max:255'],
            'company_ruc' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'company_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'company_email' => ['nullable', 'email:rfc', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_city' => ['nullable', 'string', 'max:120'],
            'company_country' => ['required', 'string', 'max:120'],
            'currency' => ['required', Rule::in(['NIO', 'USD', 'EUR'])],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', Rule::in(['d/m/Y', 'Y-m-d', 'm/d/Y'])],
            'language' => ['required', Rule::in(['es', 'en'])],
            'company_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
            'ticket_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:1024', 'dimensions:max_width=1200,max_height=1200'],
            'remove_company_logo' => ['boolean'],
            'remove_ticket_logo' => ['boolean'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'receipt_message' => ['nullable', 'string', 'max:500'],
            'system_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_ruc.regex' => 'El RUC solo puede contener letras, números y guiones.',
            'company_phone.regex' => 'El teléfono contiene caracteres no permitidos.',
            'company_logo.image' => 'El logo principal debe ser una imagen válida.',
            'company_logo.mimes' => 'El logo principal debe ser JPG, PNG o WebP.',
            'company_logo.max' => 'El logo principal no puede superar 2 MB.',
            'ticket_logo.image' => 'El logo para tickets debe ser una imagen válida.',
            'ticket_logo.mimes' => 'El logo para tickets debe ser JPG, PNG o WebP.',
            'ticket_logo.max' => 'El logo para tickets no puede superar 1 MB.',
        ];
    }
}

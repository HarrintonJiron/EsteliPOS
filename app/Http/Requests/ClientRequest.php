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
        $clientType = $this->input('client_type', 'natural');
        $isQuickForm = $this->boolean('quick_client_form');

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'code')->ignore($clientId),
            ],
            'name' => 'required|string|max:255',
            'client_type' => ['required', Rule::in(['natural', 'company'])],
            'business_name' => [Rule::requiredIf($clientType === 'company' && ! $isQuickForm), 'nullable', 'string', 'max:255'],
            'cedula' => [
                Rule::requiredIf($clientType === 'natural' && ! $isQuickForm),
                'nullable',
                'string',
                'max:30',
                'regex:/^(?:\d{3}-\d{6}-\d{4}[A-Za-z0-9]|\d{14}[A-Za-z0-9])$/',
                Rule::unique('clients', 'cedula')->ignore($clientId),
            ],
            'ruc' => [
                Rule::requiredIf($clientType === 'company' && ! $isQuickForm),
                'nullable',
                'string',
                'max:30',
                'regex:/^(?:[A-Za-z]\d{13}|\d{3}-\d{6}-\d{4}[A-Za-z0-9]|\d{14}[A-Za-z0-9])$/',
                Rule::unique('clients', 'ruc')->ignore($clientId),
            ],
            'phone' => [Rule::requiredIf($isQuickForm), 'nullable', 'string', 'max:50'],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'taxpayer_type' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'price_list_id' => 'nullable|exists:price_lists,id',
            'credit_enabled' => 'nullable|boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:1|max:365',
            'mora_enabled' => 'nullable|boolean',
            'mora_rate' => 'nullable|numeric|min:0|max:100',
            'mora_grace_days' => 'nullable|integer|min:0|max:90',
            'mora_max_pct' => 'nullable|numeric|min:0|max:100',
        ];
    }

    protected function prepareForValidation(): void
    {
        $clientType = $this->input('client_type', 'natural');
        $isQuickForm = $this->boolean('quick_client_form');
        $saveCedulaIdentity = $this->boolean('save_cedula_identity');

        $this->merge([
            'client_type' => $clientType,
            'cedula' => $this->normalizeDocument(
                $clientType === 'natural' && (! $isQuickForm || $saveCedulaIdentity)
                    ? $this->input('cedula')
                    : null
            ),
            'ruc' => $this->normalizeDocument($clientType === 'company' ? $this->input('ruc') : null),
            'business_name' => $clientType === 'company' ? ($this->input('business_name') ?: $this->input('name')) : null,
            'status' => $this->input('status', 'active'),
            'credit_enabled' => $this->boolean('credit_enabled'),
            'credit_limit' => $this->input('credit_limit', 0),
            'credit_days' => $this->input('credit_days', 30),
            'quick_client_form' => $isQuickForm,
            'save_cedula_identity' => $saveCedulaIdentity,
        ]);
    }

    private function normalizeDocument(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'client_type.required' => 'El tipo de cliente es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio en Cliente Rápido.',
            'cedula.regex' => 'El formato de cédula no es válido.',
            'cedula.unique' => 'La cédula ya está registrada en el sistema.',
            'ruc.regex' => 'El formato de RUC no es válido.',
            'ruc.unique' => 'El RUC ya está registrado en el sistema.',
        ];
    }
}

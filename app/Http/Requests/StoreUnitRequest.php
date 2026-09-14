<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'abbreviation' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('units', 'abbreviation')],
            'unit_type' => ['required', Rule::in(['volume', 'weight', 'length', 'count', 'package', 'area'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'abbreviation.required' => 'La abreviatura es obligatoria.',
            'abbreviation.unique' => 'Ya existe una unidad con esa abreviatura.',
            'abbreviation.alpha_dash' => 'La abreviatura solo puede contener letras, números, guiones y guiones bajos.',
            'unit_type.required' => 'Selecciona el tipo de unidad.',
            'unit_type.in' => 'Tipo de unidad no válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'abbreviation' => strtolower(trim((string) $this->input('abbreviation'))),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}

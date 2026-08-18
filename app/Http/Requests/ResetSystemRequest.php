<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('configuracion.reset_system') ?? false;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['clean', 'demo'])],
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(['REINICIAR SISTEMA'])],
            'acknowledge' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.current_password' => 'La contraseña administrativa no es correcta.',
            'confirmation.in' => 'Escribe exactamente REINICIAR SISTEMA para continuar.',
            'acknowledge.accepted' => 'Debes confirmar que comprendes que los datos actuales serán eliminados.',
        ];
    }
}

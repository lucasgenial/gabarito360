<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
            'token_name' => ['sometimes', 'string', 'min:1', 'max:100'],
            'dispositivo' => ['sometimes', 'array:identificador,plataforma,modelo_dispositivo,versao_sistema,versao_app'],
            'dispositivo.identificador' => ['required_with:dispositivo', 'uuid'],
            'dispositivo.plataforma' => ['required_with:dispositivo', 'in:android'],
            'dispositivo.modelo_dispositivo' => ['sometimes', 'nullable', 'string', 'max:120'],
            'dispositivo.versao_sistema' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dispositivo.versao_app' => [
                'required_with:dispositivo',
                'string',
                'max:30',
                'regex:/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }

        if ($this->has('token_name')) {
            $this->merge([
                'token_name' => trim((string) $this->input('token_name')),
            ]);
        }

        if ($this->has('dispositivo')) {
            $device = (array) $this->input('dispositivo');

            foreach (['identificador', 'plataforma', 'modelo_dispositivo', 'versao_sistema', 'versao_app'] as $field) {
                if (isset($device[$field]) && is_string($device[$field])) {
                    $device[$field] = trim($device[$field]);
                }
            }

            $this->merge(['dispositivo' => $device]);
        }
    }
}

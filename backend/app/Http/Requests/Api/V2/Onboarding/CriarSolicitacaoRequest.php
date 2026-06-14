<?php

namespace App\Http\Requests\Api\V2\Onboarding;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CriarSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
            'nome' => ['required', 'string', 'max:180'],
            'cpf' => ['required', 'string', new Cpf],
            'perfil' => ['required', 'string', Rule::exists('perfis', 'codigo')->where('sistema', true)],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'consentimento_lgpd' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'idempotency_key' => 'Idempotency-Key',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);

        if ($this->has('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }

        if ($this->has('email')) {
            $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
        }
    }
}

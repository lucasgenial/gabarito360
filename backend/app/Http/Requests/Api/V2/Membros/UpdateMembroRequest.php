<?php

namespace App\Http\Requests\Api\V2\Membros;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membro = $this->route('membro');

        return $membro instanceof User
            && ($this->user()?->can('update', $membro) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // CPF (documento) e login (email) são imutáveis: não aceitos no update.
        return [
            'nome' => ['sometimes', 'string', 'max:180'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
            'formacao' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registro_profissional' => ['sometimes', 'nullable', 'string', 'max:120'],
            'observacoes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        $attributes = [];

        if ($this->has('nome')) {
            $attributes['nome'] = $this->input('nome');
        }

        if ($this->has('telefone')) {
            $attributes['telefone'] = $this->input('telefone');
        }

        if ($this->has('status')) {
            $attributes['status'] = $this->input('status') === 'inativo'
                ? UserStatus::INACTIVE->value
                : UserStatus::ACTIVE->value;
        }

        return $attributes;
    }
}

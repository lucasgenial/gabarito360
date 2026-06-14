<?php

namespace App\Http\Requests\Api\V2\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:180'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => [
                'sometimes',
                'string',
                'email:rfc',
                'max:254',
                Rule::unique('usuarios', 'email')->ignore($this->user()->getKey()),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }

        if ($this->has('email')) {
            $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
        }
    }
}

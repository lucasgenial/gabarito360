<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:180'],
            'email' => [
                'required',
                'email:rfc',
                'max:254',
                Rule::unique('usuarios', 'email')->ignore($this->user()?->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}

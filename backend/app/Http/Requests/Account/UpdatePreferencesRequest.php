<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tema' => ['required', Rule::in(['claro', 'escuro'])],
            'densidade' => ['required', Rule::in(['compacta', 'confortavel'])],
            'contraste_alto' => ['sometimes', 'boolean'],
            'reduzir_movimento' => ['sometimes', 'boolean'],
            'idioma' => ['required', Rule::in(['pt-BR'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contraste_alto' => $this->boolean('contraste_alto'),
            'reduzir_movimento' => $this->boolean('reduzir_movimento'),
        ]);
    }
}

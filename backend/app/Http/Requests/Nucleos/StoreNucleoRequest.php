<?php

namespace App\Http\Requests\Nucleos;

use App\Models\Nucleo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreNucleoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Nucleo::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('nucleos', 'codigo')->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'max:180'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => Str::upper(trim((string) $this->input('codigo'))),
            'nome' => trim((string) $this->input('nome')),
            'municipio' => $this->nullableTrimmed('municipio'),
            'estado' => $this->nullableUppercase('estado'),
            'email' => $this->nullableLowercase('email'),
            'telefone' => $this->nullableTrimmed('telefone'),
        ]);
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }

    private function nullableUppercase(string $field): ?string
    {
        $value = $this->nullableTrimmed($field);

        return $value === null ? null : Str::upper($value);
    }

    private function nullableLowercase(string $field): ?string
    {
        $value = $this->nullableTrimmed($field);

        return $value === null ? null : Str::lower($value);
    }
}

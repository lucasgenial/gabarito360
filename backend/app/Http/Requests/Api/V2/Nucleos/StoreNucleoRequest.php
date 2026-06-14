<?php

namespace App\Http\Requests\Api\V2\Nucleos;

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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('nucleos', 'codigo')->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'max:180'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'codigo' => $this->has('codigo') ? Str::upper(trim((string) $this->input('codigo'))) : null,
            'nome' => $this->has('nome') ? trim((string) $this->input('nome')) : null,
            'estado' => $this->has('estado') ? Str::upper(trim((string) $this->input('estado'))) : null,
            'email' => $this->has('email') ? Str::lower(trim((string) $this->input('email'))) : null,
        ], fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    public function validatedAttributes(): array
    {
        return $this->safe()->only(['codigo', 'nome', 'municipio', 'estado', 'email', 'telefone']);
    }
}

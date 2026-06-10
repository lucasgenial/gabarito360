<?php

namespace App\Http\Requests\Escolas;

use App\Models\Escola;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEscolaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $escola = $this->route('escola');

        return $escola instanceof Escola
            && ($this->user()?->can('update', $escola) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var Escola $escola */
        $escola = $this->route('escola');

        return [
            'nucleo_id' => ['prohibited'],
            'status' => ['prohibited'],
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('escolas', 'codigo')
                    ->where('nucleo_id', $escola->nucleo_id)
                    ->whereNull('deleted_at')
                    ->ignore($escola->id),
            ],
            'nome' => ['sometimes', 'required', 'string', 'max:180'],
            'municipio' => ['sometimes', 'required', 'string', 'max:120'],
            'estado' => ['sometimes', 'required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'endereco' => ['sometimes', 'nullable', 'array:logradouro,numero,complemento,bairro,cep'],
            'endereco.logradouro' => ['nullable', 'string', 'max:180'],
            'endereco.numero' => ['nullable', 'string', 'max:30'],
            'endereco.complemento' => ['nullable', 'string', 'max:120'],
            'endereco.bairro' => ['nullable', 'string', 'max:120'],
            'endereco.cep' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:254'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['nome', 'municipio', 'telefone'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = $this->nullableTrimmed($field);
            }
        }

        if ($this->has('codigo')) {
            $normalized['codigo'] = Str::upper(trim((string) $this->input('codigo')));
        }

        if ($this->has('estado')) {
            $normalized['estado'] = Str::upper(trim((string) $this->input('estado')));
        }

        if (is_array($this->input('endereco'))) {
            $normalized['endereco'] = $this->normalizedAddress();
        }

        if ($this->has('email')) {
            $email = $this->nullableTrimmed('email');
            $normalized['email'] = $email === null ? null : Str::lower($email);
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $editableFields = ['codigo', 'nome', 'municipio', 'estado', 'endereco', 'email', 'telefone'];
            $hasEditableField = collect($editableFields)
                ->contains(fn (string $field): bool => $this->exists($field));

            if (! $hasEditableField) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');
            }
        });
    }

    /** @return array<string, string|null> */
    private function normalizedAddress(): array
    {
        return collect($this->input('endereco'))
            ->map(fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null)
            ->all();
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}

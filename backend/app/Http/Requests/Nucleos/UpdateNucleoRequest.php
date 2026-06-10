<?php

namespace App\Http\Requests\Nucleos;

use App\Models\Nucleo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateNucleoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nucleo = $this->route('nucleo');

        return $nucleo instanceof Nucleo
            && ($this->user()?->can('update', $nucleo) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],
            'status' => ['prohibited'],
            'nome' => ['sometimes', 'required', 'string', 'max:180'],
            'municipio' => ['sometimes', 'nullable', 'string', 'max:120'],
            'estado' => ['sometimes', 'nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
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

        if ($this->has('estado')) {
            $estado = $this->nullableTrimmed('estado');
            $normalized['estado'] = $estado === null ? null : Str::upper($estado);
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
            $editableFields = ['nome', 'municipio', 'estado', 'email', 'telefone'];
            $hasEditableField = collect($editableFields)
                ->contains(fn (string $field): bool => $this->exists($field));

            if (! $hasEditableField) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');
            }
        });
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}

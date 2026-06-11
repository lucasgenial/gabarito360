<?php

namespace App\Http\Requests\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('usuario');

        return $target instanceof User
            && ($this->user()?->can('update', $target) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('usuario');

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:180'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:254',
                Rule::unique('usuarios', 'email')->whereNull('deleted_at')->ignore($target->id),
            ],
            'documento' => ['sometimes', 'nullable', 'string', 'max:30'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['nome', 'documento', 'telefone'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = $this->nullableTrimmed($field);
            }
        }

        if ($this->has('email')) {
            $normalized['email'] = Str::lower(trim((string) $this->input('email')));
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $editableFields = ['nome', 'email', 'documento', 'telefone'];

            if (! collect($editableFields)->contains(fn (string $field): bool => $this->exists($field))) {
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

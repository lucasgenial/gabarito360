<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\StatusEnum;
use App\Http\Requests\Usuarios\Concerns\ResolvesProfileAssignment;
use App\Models\User;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUsuarioRequest extends FormRequest
{
    use ResolvesProfileAssignment;

    public function authorize(UserAdministrationScope $scope): bool
    {
        $actor = $this->user();

        return $actor instanceof User && $this->assignmentIsAuthorized($actor, $scope);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:180'],
            'email' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique('usuarios', 'email')->whereNull('deleted_at')],
            'documento' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            'perfil_id' => [
                'required',
                'uuid',
                Rule::exists('perfis', 'id')->where('status', StatusEnum::ACTIVE->value),
            ],
            'nucleo_id' => [
                'nullable',
                'uuid',
                Rule::exists('nucleos', 'id')->where('status', StatusEnum::ACTIVE->value)->whereNull('deleted_at'),
            ],
            'escola_id' => [
                'nullable',
                'uuid',
                Rule::exists('escolas', 'id')->where('status', StatusEnum::ACTIVE->value)->whereNull('deleted_at'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'documento' => $this->nullableTrimmed('documento'),
            'telefone' => $this->nullableTrimmed('telefone'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $this->addAssignmentValidation($validator);
    }

    /** @return array<string, mixed> */
    public function userAttributes(): array
    {
        return collect($this->validated())
            ->only(['nome', 'email', 'documento', 'telefone', 'password'])
            ->all();
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}

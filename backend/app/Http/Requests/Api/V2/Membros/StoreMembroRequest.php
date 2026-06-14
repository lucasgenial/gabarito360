<?php

namespace App\Http\Requests\Api\V2\Membros;

use App\Enums\AccessScope;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Escola;
use App\Models\Perfil;
use App\Models\User;
use App\Rules\Cpf;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMembroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $escola = $this->route('escola');
        $perfil = $this->perfil();

        if (! $escola instanceof Escola || ! $perfil instanceof Perfil) {
            return $this->user()?->can('viewAny', User::class) ?? false;
        }

        return app(UserAdministrationScope::class)->canAssign($this->user(), $perfil, null, $escola);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:180'],
            'cpf' => ['nullable', 'string', new Cpf, Rule::unique('usuarios', 'documento')->whereNull('deleted_at')],
            'email' => ['nullable', 'email:rfc', 'max:254', Rule::unique('usuarios', 'email')->whereNull('deleted_at')],
            'telefone' => ['nullable', 'string', 'max:30'],
            'perfil' => [
                'required', 'string',
                Rule::exists('perfis', 'codigo')->where('sistema', true)->where('status', 'ativo'),
            ],
            'data_inicio' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
            'formacao' => ['nullable', 'string', 'max:255'],
            'registro_profissional' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'disciplinas' => ['sometimes', 'array'],
            'disciplinas.*' => ['uuid', Rule::exists('disciplinas', 'id')],
            'turmas' => ['sometimes', 'array'],
            'turmas.*' => ['uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $perfil = $this->perfil();

            if (! $perfil instanceof Perfil) {
                return;
            }

            $scope = UserRole::tryFrom($perfil->codigo)?->allowedScope();

            if (! in_array($scope, [AccessScope::SCHOOL, AccessScope::OPERATIONAL], strict: true)) {
                $validator->errors()->add('perfil', 'Este perfil nao pode ser atribuido no escopo de escola.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'nome' => $this->has('nome') ? trim((string) $this->input('nome')) : null,
            'cpf' => $this->has('cpf') ? preg_replace('/\D/', '', (string) $this->input('cpf')) : null,
            'email' => $this->has('email') ? Str::lower(trim((string) $this->input('email'))) : null,
        ], fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    public function userAttributes(): array
    {
        $status = $this->input('status', 'ativo') === 'inativo'
            ? UserStatus::INACTIVE->value
            : UserStatus::ACTIVE->value;

        return [
            'nome' => $this->input('nome'),
            'email' => $this->input('email'),
            'documento' => $this->input('cpf'),
            'telefone' => $this->input('telefone'),
            'status' => $status,
            'password' => Str::password(16),
        ];
    }

    /** @return array{perfil_id: ?string, inicio_at: Carbon} */
    public function assignmentAttributes(): array
    {
        $dataInicio = $this->input('data_inicio');

        return [
            'perfil_id' => $this->perfil()?->id,
            'inicio_at' => is_string($dataInicio) ? Carbon::parse($dataInicio) : now(),
        ];
    }

    /** @return array{disciplinas: list<string>, inicio_em: string} */
    public function extras(): array
    {
        $dataInicio = $this->input('data_inicio');

        return [
            'disciplinas' => array_values((array) $this->input('disciplinas', [])),
            'inicio_em' => is_string($dataInicio) ? $dataInicio : now()->toDateString(),
        ];
    }

    public function perfil(): ?Perfil
    {
        $codigo = $this->input('perfil');

        return is_string($codigo) && $codigo !== ''
            ? Perfil::query()->where('codigo', $codigo)->first()
            : null;
    }
}

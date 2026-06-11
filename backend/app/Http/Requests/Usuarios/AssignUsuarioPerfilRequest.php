<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\StatusEnum;
use App\Http\Requests\Usuarios\Concerns\ResolvesProfileAssignment;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignUsuarioPerfilRequest extends FormRequest
{
    use ResolvesProfileAssignment;

    public function authorize(UserAdministrationScope $scope): bool
    {
        $actor = $this->user();
        $target = $this->route('usuario');

        return $actor instanceof User
            && $target instanceof User
            && $scope->canView($actor, $target)
            && $this->assignmentIsAuthorized($actor, $scope);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
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

    public function withValidator(Validator $validator): void
    {
        $this->addAssignmentValidation($validator);

        $validator->after(function (Validator $validator): void {
            $target = $this->route('usuario');

            if (! $target instanceof User || $validator->errors()->isNotEmpty()) {
                return;
            }

            $attributes = $this->assignmentAttributes();
            $alreadyActive = UsuarioPerfil::query()
                ->where('usuario_id', $target->id)
                ->where('perfil_id', $attributes['perfil_id'])
                ->where('nucleo_id', $attributes['nucleo_id'])
                ->where('escola_id', $attributes['escola_id'])
                ->whereNull('fim_at')
                ->exists();

            if ($alreadyActive) {
                $validator->errors()->add('perfil_id', 'O usuario ja possui este perfil ativo no escopo.');
            }
        });
    }
}

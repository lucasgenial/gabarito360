<?php

namespace App\Http\Requests\Turmas;

use App\Enums\AplicadorTurmaPapel;
use App\Enums\StatusEnum;
use App\Enums\UserStatus;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\UsuarioPerfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAplicadorTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = $this->route('turma');

        return $class instanceof Turma
            && ($this->user()?->can('assignStaff', $class) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var Turma $class */
        $class = $this->route('turma');

        return [
            'usuario_id' => [
                'required',
                'uuid',
                Rule::exists('usuarios', 'id')
                    ->where('status', UserStatus::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'papel' => [
                'required',
                Rule::in([
                    AplicadorTurmaPapel::TEACHER->value,
                    AplicadorTurmaPapel::APPLICATOR->value,
                ]),
                Rule::unique('aplicadores_turmas', 'papel')
                    ->where('turma_id', $class->id)
                    ->where('usuario_id', $this->input('usuario_id'))
                    ->whereNull('fim_em'),
            ],
            'inicio_em' => ['required', 'date'],
            'fim_em' => ['prohibited'],
            'vinculado_por' => ['prohibited'],
            'turma_id' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $class = $this->route('turma');
            $role = AplicadorTurmaPapel::tryFrom((string) $this->input('papel'))?->requiredRole();
            $userId = $this->input('usuario_id');

            if (! $class instanceof Turma || $role === null || ! is_string($userId) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $profileId = Perfil::query()
                ->where('codigo', $role->value)
                ->where('status', StatusEnum::ACTIVE->value)
                ->value('id');

            if (! is_string($profileId) || ! UsuarioPerfil::query()
                ->where('usuario_id', $userId)
                ->where('perfil_id', $profileId)
                ->where('escola_id', $class->escola_id)
                ->where('inicio_at', '<=', now())
                ->whereNull('fim_at')
                ->exists()) {
                $validator->errors()->add(
                    'usuario_id',
                    'O usuario deve possuir perfil operacional ativo correspondente na escola da turma.',
                );
            }
        });
    }
}

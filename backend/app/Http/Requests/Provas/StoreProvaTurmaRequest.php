<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProvaTurma;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\ProvaTurmaScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProvaTurmaRequest extends FormRequest
{
    use ResolvesScopedProvaTurma;

    public function authorize(): bool
    {
        $exam = $this->resolveScopedProva();

        return $exam instanceof Prova
            && ($this->user()?->can('viewClassLinks', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'turma_id' => ['required', 'uuid'],
            'data_prevista' => ['sometimes', 'nullable', 'date'],
            'prova_id' => ['prohibited'],
            'vinculado_por' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $actor = $this->user();
            $scope = app(ProvaTurmaScope::class);
            $class = ! $actor instanceof User
                ? null
                : $scope->applyTurmas(Turma::query()->with('escola.nucleo'), $actor)
                    ->find($this->input('turma_id'));

            if (! $class instanceof Turma || ! $actor instanceof User || ! $scope->canLink($actor, $this->prova(), $class)) {
                $validator->errors()->add('turma_id', 'A turma nao esta disponivel para vinculo com esta prova.');

                return;
            }

            if ($this->prova()->provaTurmas()->where('turma_id', $class->id)->exists()) {
                $validator->errors()->add('turma_id', 'A prova ja esta vinculada a turma informada.');

                return;
            }

            $this->attributes->set('selected_exam_class', $class);
        });
    }

    public function turma(): Turma
    {
        /** @var Turma $class */
        $class = $this->attributes->get('selected_exam_class');

        return $class;
    }
}

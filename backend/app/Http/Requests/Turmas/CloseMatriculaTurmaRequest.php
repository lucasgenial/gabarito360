<?php

namespace App\Http\Requests\Turmas;

use App\Enums\MatriculaTurmaStatus;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseMatriculaTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->route('turma');
        $matricula = $this->route('matricula');

        return $turma instanceof Turma
            && $matricula instanceof MatriculaTurma
            && $matricula->status === MatriculaTurmaStatus::ACTIVE
            && ($this->user()?->can('closeEnrollment', $turma) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var MatriculaTurma $matricula */
        $matricula = $this->route('matricula');

        return [
            'status' => ['required', Rule::in([
                MatriculaTurmaStatus::TRANSFERRED->value,
                MatriculaTurmaStatus::CLOSED->value,
            ])],
            'fim_em' => ['required', 'date', 'after_or_equal:'.$matricula->inicio_em->toDateString()],
            'aluno_id' => ['prohibited'],
            'turma_id' => ['prohibited'],
            'ano_letivo' => ['prohibited'],
            'inicio_em' => ['prohibited'],
            'numero_chamada' => ['prohibited'],
        ];
    }
}

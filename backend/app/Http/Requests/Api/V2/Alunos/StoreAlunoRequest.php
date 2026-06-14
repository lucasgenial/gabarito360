<?php

namespace App\Http\Requests\Api\V2\Alunos;

use App\Models\Aluno;
use App\Models\Turma;
use Illuminate\Validation\Rule;

class StoreAlunoRequest extends AlunoApiRequest
{
    public function authorize(): bool
    {
        $turma = $this->turma();

        if (! $turma instanceof Turma) {
            return $this->user()?->can('viewAny', Aluno::class) ?? false;
        }

        return $this->user()?->can('create', [Aluno::class, $turma->escola]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $escolaId = $this->turma()?->escola_id;

        return [
            ...$this->alunoFieldRules(),
            'matricula' => [
                'required', 'string', 'max:80',
                Rule::unique('alunos', 'matricula')->where('escola_id', $escolaId)->whereNull('deleted_at'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        return $this->mappedAlunoAttributes();
    }
}

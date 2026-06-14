<?php

namespace App\Http\Requests\Api\V2\Alunos;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\Turma;
use App\Rules\Cpf;
use Illuminate\Validation\Rule;

class UpdateAlunoRequest extends AlunoApiRequest
{
    public function authorize(): bool
    {
        $aluno = $this->route('aluno');

        return $aluno instanceof Aluno
            && ($this->user()?->can('update', $aluno) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Aluno|null $aluno */
        $aluno = $this->route('aluno');

        return [
            'nome' => ['sometimes', 'string', 'max:180'],
            'matricula' => [
                'sometimes', 'string', 'max:80',
                Rule::unique('alunos', 'matricula')
                    ->where('escola_id', $aluno?->escola_id)
                    ->ignore($aluno?->id)
                    ->whereNull('deleted_at'),
            ],
            'turma_id' => [
                'sometimes', 'uuid',
                Rule::exists('turmas', 'id')->where('status', StatusEnum::ACTIVE->value)->whereNull('deleted_at'),
            ],
            'data_nascimento' => ['sometimes', 'nullable', 'date', 'before:today'],
            'responsavel' => ['sometimes', 'nullable', 'string', 'max:180'],
            'cpf' => ['sometimes', 'nullable', 'string', new Cpf],
            'genero' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo', 'transferido'])],
        ];
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        return $this->mappedAlunoAttributes();
    }

    public function turmaDestino(): ?Turma
    {
        return $this->turma();
    }
}

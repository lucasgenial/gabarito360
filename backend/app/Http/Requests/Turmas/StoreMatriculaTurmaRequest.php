<?php

namespace App\Http\Requests\Turmas;

use App\Enums\MatriculaTurmaStatus;
use App\Enums\StatusEnum;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMatriculaTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->route('turma');

        return $turma instanceof Turma
            && ($this->user()?->can('createEnrollment', $turma) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var Turma $turma */
        $turma = $this->route('turma');

        return [
            'aluno_id' => [
                'required',
                'uuid',
                Rule::exists('alunos', 'id')
                    ->where('escola_id', $turma->escola_id)
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'numero_chamada' => ['nullable', 'string', 'max:20'],
            'inicio_em' => ['required', 'date'],
            'ano_letivo' => ['prohibited'],
            'status' => ['prohibited'],
            'fim_em' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('numero_chamada')) {
            $value = trim((string) $this->input('numero_chamada'));
            $this->merge(['numero_chamada' => $value === '' ? null : $value]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $turma = $this->route('turma');
            $alunoId = $this->input('aluno_id');

            if (! $turma instanceof Turma || ! is_string($alunoId) || $validator->errors()->isNotEmpty()) {
                return;
            }

            if (MatriculaTurma::query()
                ->where('aluno_id', $alunoId)
                ->where('ano_letivo', $turma->ano_letivo)
                ->where('status', MatriculaTurmaStatus::ACTIVE->value)
                ->exists()) {
                $validator->errors()->add('aluno_id', 'O aluno ja possui matricula ativa neste ano letivo.');
            }
        });
    }
}

<?php

namespace App\Http\Requests\Api\V2\Alunos;

use App\Enums\StatusEnum;
use App\Models\Turma;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mapeia o shape do contrato V2 (cpf/responsavel/turma_id/status) para as
 * colunas da tabela `alunos`. `responsavel`/`turma_id` são consumidos pelas
 * Actions (matrícula/responsável), não vão para `alunos`.
 */
abstract class AlunoApiRequest extends FormRequest
{
    /** @return array<string, mixed> */
    protected function alunoFieldRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:180'],
            'matricula' => ['required', 'string', 'max:80'],
            'turma_id' => [
                'required', 'uuid',
                Rule::exists('turmas', 'id')->where('status', StatusEnum::ACTIVE->value)->whereNull('deleted_at'),
            ],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'responsavel' => ['nullable', 'string', 'max:180'],
            'cpf' => ['nullable', 'string', new Cpf],
            'genero' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo', 'transferido'])],
        ];
    }

    /** @return array<string, mixed> */
    protected function mappedAlunoAttributes(): array
    {
        $attributes = [];

        if ($this->has('nome')) {
            $attributes['nome'] = trim((string) $this->input('nome'));
        }

        if ($this->has('matricula')) {
            $attributes['matricula'] = trim((string) $this->input('matricula'));
        }

        if ($this->has('data_nascimento')) {
            $attributes['data_nascimento'] = $this->input('data_nascimento');
        }

        if ($this->has('genero')) {
            $attributes['genero'] = $this->input('genero');
        }

        if ($this->has('cpf')) {
            $attributes['documento'] = $this->filled('cpf')
                ? preg_replace('/\D/', '', (string) $this->input('cpf'))
                : null;
        }

        if ($this->has('status')) {
            // `transferido` é estado de matrícula, não de aluno → trata como ativo.
            $attributes['status'] = $this->input('status') === 'inativo'
                ? StatusEnum::INACTIVE->value
                : StatusEnum::ACTIVE->value;
        }

        return $attributes;
    }

    public function turma(): ?Turma
    {
        $id = $this->input('turma_id');

        return is_string($id) && Str::isUuid($id)
            ? Turma::query()->with('escola')->find($id)
            : null;
    }
}

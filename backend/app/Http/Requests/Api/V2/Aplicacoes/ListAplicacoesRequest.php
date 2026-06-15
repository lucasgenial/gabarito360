<?php

namespace App\Http\Requests\Api\V2\Aplicacoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAplicacoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'prova' => ['nullable', 'uuid'],
            'turma' => ['nullable', 'uuid'],
            'escola' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in(['rascunho', 'agendada', 'em_andamento', 'finalizada', 'cancelada'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

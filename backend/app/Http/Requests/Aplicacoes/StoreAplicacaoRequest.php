<?php

namespace App\Http\Requests\Aplicacoes;

use App\Models\Aplicacao;
use App\Models\Escola;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAplicacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = Turma::query()->with('escola')->find($this->input('turma_id'));

        return $class instanceof Turma
            && $class->escola instanceof Escola
            && Gate::allows('create', [Aplicacao::class, $class->escola]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'prova_id' => ['required', 'uuid', 'exists:provas,id'],
            'turma_id' => ['required', 'uuid', 'exists:turmas,id'],
            'gabarito_oficial_id' => ['required', 'uuid', 'exists:gabaritos_oficiais,id'],
            'titulo' => ['required', 'string', 'max:180'],
            'inicio_previsto_at' => ['nullable', 'date'],
            'fim_previsto_at' => ['nullable', 'date', 'after_or_equal:inicio_previsto_at'],
        ];
    }
}

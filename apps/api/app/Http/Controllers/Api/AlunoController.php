<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\EspacoPessoal;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlunoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query    = Escopo::alunos($request->user());

        if ($turmaId = $request->query('turma_id')) {
            $query->where('turma_id', $turmaId);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('matricula', 'like', "%{$busca}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        $alunos = $query->with('turma:id,nome,serie,escola_id')
            ->orderBy('nome')
            ->get()
            ->map(fn (Aluno $a) => array_merge($a->toArray(), [
                'turma_nome' => $a->turma?->nome,
                'turma_serie'=> $a->turma?->serie,
            ]));

        $baseQ = Escopo::alunos($request->user());

        return ApiResponse::success([
            'alunos' => $alunos,
            'kpis'   => [
                'total_alunos' => $baseQ->count(),
                'total_ativos' => (clone $baseQ)->where('ativo', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $pessoal = EspacoPessoal::validarAluno($request);
        $request->validate(['turma_id' => 'required|integer']);
        $turma = Escopo::turmas($request->user())->findOrFail((int) $request->input('turma_id'));
        $espaco = Espacos::ativo() ? (int) $turma->espaco_id : null;
        $data = $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'turma_id'        => 'required|integer|exists:turmas,id',
            'nome'            => 'required|string|max:200',
            'matricula'       => ['required', 'string', 'max:20', Rule::unique('alunos', 'matricula')->when($espaco !== null, fn ($q) => $q->where('espaco_id', $espaco))],
            'cpf'             => ['sometimes', 'string', 'size:11', 'nullable', new \App\Rules\UnicoProtegido('alunos', 'cpf', espaco: $espaco)],
            'data_nascimento' => $pessoal ? 'prohibited' : 'required|date',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
            'foto'            => 'prohibited',
            'foto_path'       => 'prohibited',
        ]);

        $aluno = new Aluno($pessoal ? EspacoPessoal::dadosMinimos($data) : $data);
        if (Espacos::ativo()) $aluno->forceFill(Espacos::autoria((int) $turma->espaco_id, $request->user()));
        if ($pessoal) $aluno->forceFill(['espaco_tipo'=>'pessoal']);
        $aluno->save();

        return ApiResponse::success($aluno->load('turma:id,nome,serie'), null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query    = Escopo::alunos($request->user())->with('turma:id,nome,serie,escola_id');

        $aluno = $query->find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        return ApiResponse::success(array_merge($aluno->toArray(), [
            'turma_nome'  => $aluno->turma?->nome,
            'turma_serie' => $aluno->turma?->serie,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pessoal = EspacoPessoal::validarAluno($request);
        $aluno = Escopo::alunos($request->user())->find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $data = $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'turma_id'        => 'sometimes|integer|exists:turmas,id',
            'nome'            => 'sometimes|string|max:200',
            'matricula'       => ['sometimes', 'string', 'max:20', Rule::unique('alunos', 'matricula')->ignore($aluno->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $aluno->espaco_id))],
            'cpf'             => ['sometimes', 'string', 'size:11', 'nullable', new \App\Rules\UnicoProtegido('alunos', 'cpf', $id, Espacos::ativo() ? (int) $aluno->espaco_id : null)],
            'data_nascimento' => 'sometimes|date|nullable',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
            'foto'            => 'prohibited',
            'foto_path'       => 'prohibited',
        ]);

        if (isset($data['turma_id'])) {
            $destino = Escopo::turmas($request->user())->findOrFail((int) $data['turma_id']);
            $origem = Escopo::turmas($request->user())->findOrFail($aluno->turma_id);
            abort_unless((int) $destino->escola_id === (int) $origem->escola_id, 422, 'Transferência entre escolas exige procedimento próprio.');
            if (Espacos::ativo()) abort_unless((int) $destino->espaco_id === (int) $aluno->espaco_id, 422);
        }
        if ($pessoal) {
            app(\App\Services\AtualizarAlunoPessoal::class)->executar($request->user(), $id, EspacoPessoal::dadosMinimos($data));
        } else {
            $aluno->update($data);
        }

        return ApiResponse::success($aluno->fresh());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $aluno = Escopo::alunos($request->user())->find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $aluno->update(['ativo' => !$aluno->ativo]);

        return ApiResponse::success(
            ['ativo' => $aluno->ativo],
            $aluno->ativo ? 'Aluno ativado.' : 'Aluno desativado.'
        );
    }
}

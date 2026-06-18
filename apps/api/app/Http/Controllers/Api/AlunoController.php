<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlunoController extends Controller
{
    private function escopoTurmaIds(Request $request): ?array
    {
        $usuario = $request->user();

        return match ($usuario->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('turmas')
                ->join('escolas', 'turmas.escola_id', '=', 'escolas.id')
                ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
                ->join('usuario_escopos', function ($j) use ($usuario) {
                    $j->on('nucleos.id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $usuario->id)
                      ->where('usuario_escopos.escopo_tipo', 'nucleo');
                })
                ->pluck('turmas.id')->all(),
            'dir_escolar', 'coordenador' => DB::table('turmas')
                ->join('usuario_escopos', function ($j) use ($usuario) {
                    $j->on('turmas.escola_id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $usuario->id)
                      ->where('usuario_escopos.escopo_tipo', 'escola');
                })
                ->pluck('turmas.id')->all(),
            'professor' => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'turma')
                ->pluck('escopo_id')->all(),
            default => [],
        };
    }

    public function index(Request $request): JsonResponse
    {
        $turmaIds = $this->escopoTurmaIds($request);
        $query    = Aluno::query();

        if ($turmaIds !== null) {
            $query->whereIn('turma_id', $turmaIds);
        }

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

        $baseQ = Aluno::query();
        if ($turmaIds !== null) $baseQ->whereIn('turma_id', $turmaIds);

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
        $data = $request->validate([
            'turma_id'        => 'required|integer|exists:turmas,id',
            'nome'            => 'required|string|max:200',
            'matricula'       => 'required|string|max:20|unique:alunos,matricula',
            'data_nascimento' => 'sometimes|date|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
        ]);

        $aluno = Aluno::create($data);

        return ApiResponse::success($aluno->load('turma:id,nome,serie'), 'Aluno cadastrado com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $turmaIds = $this->escopoTurmaIds($request);
        $query    = Aluno::with('turma:id,nome,serie,escola_id');

        if ($turmaIds !== null) {
            $query->whereIn('turma_id', $turmaIds);
        }

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
        $aluno = Aluno::find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $data = $request->validate([
            'turma_id'        => 'sometimes|integer|exists:turmas,id',
            'nome'            => 'sometimes|string|max:200',
            'matricula'       => "sometimes|string|max:20|unique:alunos,matricula,{$id}",
            'data_nascimento' => 'sometimes|date|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
        ]);

        $aluno->update($data);

        return ApiResponse::success($aluno->fresh(), 'Aluno atualizado com sucesso.');
    }

    public function toggle(int $id): JsonResponse
    {
        $aluno = Aluno::find($id);

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

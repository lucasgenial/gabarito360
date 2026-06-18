<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TurmaController extends Controller
{
    private function escopoIds(Request $request): ?array
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
        $ids   = $this->escopoIds($request);
        $query = Turma::query();

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('serie', 'like', "%{$busca}%");
            });
        }

        if ($escolaId = $request->query('escola_id')) {
            $query->where('escola_id', $escolaId);
        }

        if ($anoLetivo = $request->query('ano_letivo')) {
            $query->where('ano_letivo', $anoLetivo);
        }

        if ($request->has('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        $turmas = $query->with('escola:id,nome,nucleo_id')
            ->orderBy('nome')
            ->get()
            ->map(function (Turma $t) {
                $totalAlunos = DB::table('alunos')
                    ->where('turma_id', $t->id)
                    ->where('ativo', true)
                    ->count();

                return array_merge($t->toArray(), [
                    'total_alunos' => $totalAlunos,
                    'escola_nome'  => $t->escola?->nome,
                ]);
            });

        $baseQ = Turma::query();
        if ($ids !== null) $baseQ->whereIn('id', $ids);

        $kpis = [
            'total_turmas'  => $baseQ->count(),
            'total_ativas'  => (clone $baseQ)->where('ativo', true)->count(),
            'total_alunos'  => (int) DB::table('alunos')
                ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
                ->when($ids !== null, fn ($q) => $q->whereIn('turmas.id', $ids))
                ->where('alunos.ativo', true)
                ->count(),
        ];

        return ApiResponse::success(['turmas' => $turmas, 'kpis' => $kpis]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'escola_id'  => 'required|integer|exists:escolas,id',
            'nome'       => 'required|string|max:50',
            'serie'      => 'required|string|max:50',
            'turno'      => ['required', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'required|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
        ]);

        $turma = Turma::create($data);

        return ApiResponse::success($turma, 'Turma criada com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ids   = $this->escopoIds($request);
        $query = Turma::with('escola:id,nome,nucleo_id');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        $turma = $query->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $alunos = DB::table('alunos')
            ->where('turma_id', $id)
            ->orderBy('nome')
            ->select('id', 'nome', 'matricula', 'data_nascimento', 'ativo')
            ->get();

        $totalAlunos = $alunos->where('ativo', true)->count();

        return ApiResponse::success(array_merge($turma->toArray(), [
            'total_alunos' => $totalAlunos,
            'escola_nome'  => $turma->escola?->nome,
            'alunos'       => $alunos,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $turma = Turma::find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $data = $request->validate([
            'escola_id'  => 'sometimes|integer|exists:escolas,id',
            'nome'       => 'sometimes|string|max:50',
            'serie'      => 'sometimes|string|max:50',
            'turno'      => ['sometimes', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'sometimes|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
        ]);

        $turma->update($data);

        return ApiResponse::success($turma->fresh(), 'Turma atualizada com sucesso.');
    }

    public function toggle(int $id): JsonResponse
    {
        $turma = Turma::find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $turma->update(['ativo' => !$turma->ativo]);

        return ApiResponse::success(
            ['ativo' => $turma->ativo],
            $turma->ativo ? 'Turma ativada.' : 'Turma desativada.'
        );
    }
}

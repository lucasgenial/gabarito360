<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Escola;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EscolaController extends Controller
{
    private function escopoIds(Request $request): ?array
    {
        $usuario = $request->user();

        return match ($usuario->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('escolas')
                ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
                ->join('usuario_escopos', 'nucleos.id', '=', 'usuario_escopos.escopo_id')
                ->where('usuario_escopos.usuario_id', $usuario->id)
                ->where('usuario_escopos.escopo_tipo', 'nucleo')
                ->pluck('escolas.id')
                ->all(),
            default => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->pluck('escopo_id')
                ->all(),
        };
    }

    public function index(Request $request): JsonResponse
    {
        $ids   = $this->escopoIds($request);
        $query = Escola::query();

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('cidade', 'like', "%{$busca}%")
                  ->orWhere('inep', 'like', "%{$busca}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        $escolas = $query->with('nucleo:id,nome')
            ->orderBy('nome')
            ->get()
            ->map(function (Escola $e) {
                $stats = DB::table('turmas')->where('escola_id', $e->id)
                    ->selectRaw('count(*) as turmas, sum(total_alunos) as alunos')
                    ->first();

                $provas = DB::table('provas')->where('escola_id', $e->id)->count();

                return array_merge($e->toArray(), [
                    'total_turmas' => (int) ($stats->turmas ?? 0),
                    'total_alunos' => (int) ($stats->alunos ?? 0),
                    'total_provas' => $provas,
                    'nucleo_nome'  => $e->nucleo?->nome,
                ]);
            });

        // KPIs
        $baseQ = Escola::query();
        if ($ids !== null) $baseQ->whereIn('id', $ids);

        $totalEscolas  = $baseQ->count();
        $totalAtivas   = (clone $baseQ)->where('ativo', true)->count();
        $totalAlunos   = (int) DB::table('alunos')
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->when($ids !== null, fn ($q) => $q->whereIn('turmas.escola_id', $ids))
            ->count();
        $totalTurmas   = (int) DB::table('turmas')
            ->when($ids !== null, fn ($q) => $q->whereIn('escola_id', $ids))
            ->where('ativo', true)
            ->count();

        return ApiResponse::success([
            'escolas' => $escolas,
            'kpis'    => [
                'total_escolas' => $totalEscolas,
                'total_ativas'  => $totalAtivas,
                'total_alunos'  => $totalAlunos,
                'total_turmas'  => $totalTurmas,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nucleo_id' => 'required|integer|exists:nucleos,id',
            'nome'      => 'required|string|max:200',
            'inep'      => 'required|string|size:8|unique:escolas,inep',
            'tipo_rede' => ['required', Rule::in(['estadual','municipal','federal','privada'])],
            'logradouro'=> 'sometimes|string|max:255|nullable',
            'cidade'    => 'sometimes|string|max:100|nullable',
            'uf'        => 'sometimes|string|size:2|nullable',
            'telefone'  => 'sometimes|string|max:20|nullable',
            'email'     => 'sometimes|email|nullable',
            'ativo'     => 'sometimes|boolean',
        ]);

        $escola = Escola::create($data);

        return ApiResponse::success($escola, 'Escola criada com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ids    = $this->escopoIds($request);
        $query  = Escola::with('nucleo:id,nome');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        $escola = $query->find($id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $stats = DB::table('turmas')->where('escola_id', $id)
            ->selectRaw('count(*) as turmas, sum(total_alunos) as alunos')
            ->first();

        $provas = DB::table('provas')->where('escola_id', $id)->count();

        $turmas = DB::table('turmas')
            ->where('escola_id', $id)
            ->orderBy('nome')
            ->select('id', 'nome', 'ano', 'turno', 'total_alunos', 'ativo')
            ->get();

        $equipe = DB::table('usuarios')
            ->join('usuario_escopos', function ($j) use ($id) {
                $j->on('usuarios.id', '=', 'usuario_escopos.usuario_id')
                  ->where('usuario_escopos.escopo_tipo', 'escola')
                  ->where('usuario_escopos.escopo_id', $id);
            })
            ->select('usuarios.id', 'usuarios.nome', 'usuarios.email', 'usuarios.perfil', 'usuarios.ativo')
            ->orderBy('usuarios.nome')
            ->get();

        return ApiResponse::success(array_merge($escola->toArray(), [
            'total_turmas' => (int) ($stats->turmas ?? 0),
            'total_alunos' => (int) ($stats->alunos ?? 0),
            'total_provas' => $provas,
            'nucleo_nome'  => $escola->nucleo?->nome,
            'turmas'       => $turmas,
            'equipe'       => $equipe,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $escola = Escola::find($id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $data = $request->validate([
            'nucleo_id' => 'sometimes|integer|exists:nucleos,id',
            'nome'      => 'sometimes|string|max:200',
            'inep'      => ['sometimes', 'string', 'size:8', Rule::unique('escolas','inep')->ignore($id)],
            'tipo_rede' => ['sometimes', Rule::in(['estadual','municipal','federal','privada'])],
            'logradouro'=> 'sometimes|string|max:255|nullable',
            'cidade'    => 'sometimes|string|max:100|nullable',
            'uf'        => 'sometimes|string|size:2|nullable',
            'telefone'  => 'sometimes|string|max:20|nullable',
            'email'     => 'sometimes|email|nullable',
            'ativo'     => 'sometimes|boolean',
        ]);

        $escola->update($data);

        return ApiResponse::success($escola->fresh(), 'Escola atualizada com sucesso.');
    }

    public function toggle(int $id): JsonResponse
    {
        $escola = Escola::find($id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $escola->update(['ativo' => !$escola->ativo]);

        return ApiResponse::success(
            ['ativo' => $escola->ativo],
            $escola->ativo ? 'Escola ativada.' : 'Escola desativada.'
        );
    }
}

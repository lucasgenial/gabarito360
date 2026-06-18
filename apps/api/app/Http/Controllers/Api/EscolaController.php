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
                $totalTurmas = DB::table('turmas')->where('escola_id', $e->id)->count();

                $totalAlunosEscola = DB::table('alunos')
                    ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
                    ->where('turmas.escola_id', $e->id)
                    ->count();

                $provas = DB::table('provas')->where('escola_id', $e->id)->count();

                return array_merge($e->toArray(), [
                    'total_turmas' => $totalTurmas,
                    'total_alunos' => $totalAlunosEscola,
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

        return ApiResponse::success($escola, null, 201);
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

        $totalTurmas = DB::table('turmas')->where('escola_id', $id)->count();

        $totalAlunosEscola = DB::table('alunos')
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->where('turmas.escola_id', $id)
            ->count();

        $provas = DB::table('provas')->where('escola_id', $id)->count();

        $turmas = DB::table('turmas')
            ->where('escola_id', $id)
            ->orderBy('nome')
            ->select('id', 'nome', 'ano_letivo as ano', 'turno', 'ativo')
            ->get()
            ->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'nome'         => $t->nome,
                    'ano'          => $t->ano,
                    'turno'        => $t->turno,
                    'ativo'        => (bool) $t->ativo,
                    'total_alunos' => DB::table('alunos')->where('turma_id', $t->id)->where('ativo', true)->count(),
                ];
            });

        $equipe = DB::table('usuarios')
            ->join('usuario_escopos', function ($j) use ($id) {
                $j->on('usuarios.id', '=', 'usuario_escopos.usuario_id')
                  ->where('usuario_escopos.escopo_tipo', 'escola')
                  ->where('usuario_escopos.escopo_id', $id);
            })
            ->select('usuarios.id', 'usuarios.nome', 'usuarios.email', 'usuarios.perfil', 'usuarios.ativo')
            ->orderBy('usuarios.nome')
            ->get();

        $provasLista = DB::table('provas')
            ->where('escola_id', $id)
            ->orderByDesc('data_aplicacao')
            ->select('id', 'titulo', 'disciplina', 'status', 'data_aplicacao')
            ->get()
            ->map(function ($p) {
                $turmasNomes = DB::table('prova_turmas')
                    ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->implode(', ');

                $totalAlunos = DB::table('prova_turmas')
                    ->join('alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->where('alunos.ativo', true)
                    ->count();

                $media = DB::table('notas')->where('prova_id', $p->id)->avg('nota_final');

                return [
                    'id'             => $p->id,
                    'titulo'         => $p->titulo,
                    'disciplina'     => $p->disciplina,
                    'turma'          => $turmasNomes ?: '—',
                    'data_aplicacao' => $p->data_aplicacao,
                    'total_alunos'   => $totalAlunos,
                    'media'          => $media !== null ? round((float) $media, 1) : null,
                    'status'         => $p->status,
                ];
            });

        $alunosLista = DB::table('alunos')
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->where('turmas.escola_id', $id)
            ->select('alunos.id', 'alunos.nome', 'alunos.matricula', 'alunos.ativo', 'turmas.nome as turma')
            ->orderBy('alunos.nome')
            ->get()
            ->map(function ($a) {
                $media = DB::table('notas')->where('aluno_id', $a->id)->avg('nota_final');
                $totalProvas = DB::table('notas')->where('aluno_id', $a->id)->count();

                return [
                    'id'           => $a->id,
                    'nome'         => $a->nome,
                    'matricula'    => $a->matricula,
                    'turma'        => $a->turma,
                    'media_geral'  => $media !== null ? round((float) $media, 1) : null,
                    'total_provas' => $totalProvas,
                    'ativo'        => (bool) $a->ativo,
                ];
            });

        return ApiResponse::success(array_merge($escola->toArray(), [
            'total_turmas' => $totalTurmas,
            'total_alunos' => $totalAlunosEscola,
            'total_provas' => $provas,
            'nucleo_nome'  => $escola->nucleo?->nome,
            'turmas'       => $turmas,
            'equipe'       => $equipe,
            'provas'       => $provasLista,
            'alunos'       => $alunosLista,
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

        return ApiResponse::success($escola->fresh());
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

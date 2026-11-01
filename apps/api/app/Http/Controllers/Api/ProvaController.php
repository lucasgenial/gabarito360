<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\ConsultasPedagogicas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProvaController extends Controller
{
    /** Impede criar/editar prova em escola ou turma fora do escopo do usuário. */
    private function foraDoEscopo($usuario, int $escolaId, array $turmaIds): ?JsonResponse
    {
        $escolas = Escopo::escolaIds($usuario);
        if ($escolas !== null && !in_array($escolaId, $escolas, true)) {
            return Espacos::ativo() ? ApiResponse::notFound('Escola não encontrada.') : ApiResponse::error('Escola fora do seu escopo.', [], 403);
        }

        if (Espacos::ativo()) {
            $espaco = Espacos::daEscola($usuario, $escolaId);
            if (DB::table('turmas')->whereIn('id', $turmaIds)->where('espaco_id', $espaco)->count() !== count(array_unique($turmaIds))) {
                return ApiResponse::notFound('Turma não encontrada.');
            }
        }
        $turmas = Escopo::turmaIds($usuario);
        $invalidas = DB::table('turmas')->whereIn('id', $turmaIds)
            ->where(fn ($q) => $q->where('escola_id', '!=', $escolaId)
                ->when($turmas !== null, fn ($q2) => $q2->orWhereNotIn('id', $turmas ?: [0])))
            ->count();
        if ($invalidas > 0) {
            return ApiResponse::error('Turma fora do seu escopo ou de outra escola.', [], 403);
        }

        return null;
    }

    private function progresso(ConsultasPedagogicas $consulta, Prova $p): array
    {
        if ($p->status === 'corrigida') {
            $totalCartoes = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count();
            $lidos        = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->whereIn('status', ['lido', 'ambiguo'])->count();
            $media        = $consulta->tabela('notas')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->avg('nota_final');

            return [
                'tipo'          => 'media',
                'media'         => $media !== null ? round((float) $media, 1) : null,
                'cartoes_lidos' => $lidos,
                'cartoes_total' => $totalCartoes,
            ];
        }

        if ($p->status === 'em_correcao') {
            $totalCartoes = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count();
            $lidos        = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->whereIn('status', ['lido', 'ambiguo'])->count();

            return ['tipo' => 'correcao', 'lidos' => $lidos, 'total' => $totalCartoes];
        }

        if ($p->status === 'rascunho') {
            $gabarito    = $consulta->tabela('gabaritos')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->first();
            $preenchidas = $gabarito ? $consulta->tabela('gabarito_questoes')->where('gabarito_id', $gabarito->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count() : 0;

            return ['tipo' => 'gabarito', 'preenchidas' => $preenchidas, 'total' => $p->num_questoes];
        }

        return ['tipo' => 'nenhum'];
    }

    public function index(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $query = Escopo::provas($request->user());

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                  ->orWhere('disciplina', 'like', "%{$busca}%");
            });
        }

        if ($disciplina = $request->query('disciplina')) {
            $query->where('disciplina', $disciplina);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $provas = $query->with('escola:id,nome')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Prova $p) use ($consulta) {
                $turmasLista = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('turmas.espaco_id', 'prova_turmas.espaco_id'))
                    ->where('prova_turmas.prova_id', $p->id)
                    ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $p->espaco_id))
                    ->orderBy('turmas.nome')
                    ->get(['turmas.id', 'turmas.nome']);

                return array_merge($p->toArray(), [
                    'escola_nome'  => $p->escola?->nome,
                    'turmas'       => $turmasLista->pluck('nome')->all(),
                    'turmas_lista' => $turmasLista->map(fn ($t) => ['id' => (int) $t->id, 'nome' => $t->nome])->all(),
                    'total_alunos' => $consulta->tabela('prova_turmas')
                        ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                        ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('alunos.espaco_id', 'prova_turmas.espaco_id'))
                        ->where('prova_turmas.prova_id', $p->id)
                        ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $p->espaco_id))->where('alunos.ativo', true)->count(),
                    'cartoes_lidos' => $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->where('status', 'lido')->count(),
                    'progresso'   => $this->progresso($consulta, $p),
                ]);
            });

        $baseQ = Escopo::provas($request->user());

        $kpis = [
            'total'      => $baseQ->count(),
            'rascunho'   => (clone $baseQ)->where('status', 'rascunho')->count(),
            'publicada'  => (clone $baseQ)->where('status', 'publicada')->count(),
            'correcao'   => (clone $baseQ)->where('status', 'em_correcao')->count(),
            'corrigida'  => (clone $baseQ)->where('status', 'corrigida')->count(),
        ];

        return ApiResponse::success(['provas' => $provas, 'kpis' => $kpis]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $data = $request->validate([
            'escola_id'       => 'required|integer|exists:escolas,id',
            'titulo'          => 'required|string|max:255',
            'disciplina'      => 'required|string|max:100',
            'serie'           => 'required|string|max:50',
            'num_questoes'    => 'required|integer|min:1|max:100',
            'num_alternativas'=> 'required|integer|in:3,4,5',
            'nota_maxima'     => 'sometimes|numeric|min:0.5|max:100',
            'tipo_pontuacao'  => ['sometimes', Rule::in(['igual', 'personalizado'])],
            'anular_se_todas' => 'sometimes|boolean',
            'opcao_lingua'    => 'sometimes|boolean',
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => 'sometimes|array',
            'turma_ids.*'     => 'integer|distinct|exists:turmas,id',
        ]);

        $turmaIds = $data['turma_ids'] ?? [];
        unset($data['turma_ids']);

        if ($erro = $this->foraDoEscopo($usuario, (int) $data['escola_id'], $turmaIds)) {
            return $erro;
        }

        $data['criado_por'] = $usuario->id;
        $data['status']     = 'rascunho';

        $prova = DB::transaction(function () use ($data, $usuario, $turmaIds) {
            $prova = new Prova($data);
            if (Espacos::ativo()) $prova->forceFill(Espacos::autoria(Espacos::daEscola($usuario, (int) $data['escola_id']), $usuario));
            $prova->save();

            if ($turmaIds) {
                $inserts = array_map(fn ($tid) => ['prova_id' => $prova->id, 'turma_id' => $tid] + (Espacos::ativo() ? Espacos::autoria((int) $prova->espaco_id, $usuario) : []), $turmaIds);
                DB::table('prova_turmas')->insert($inserts);
            }
            return $prova;
        });

        return ApiResponse::success($prova, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $prova = Escopo::provas($request->user())->with('escola:id,nome')->find($id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('turmas.espaco_id', 'prova_turmas.espaco_id'))
            ->where('prova_turmas.prova_id', $id)
            ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $prova->espaco_id))
            ->select('turmas.id', 'turmas.nome', 'turmas.serie')
            ->get();

        return ApiResponse::success(array_merge($prova->toArray(), [
            'escola_nome' => $prova->escola?->nome,
            'turmas'      => $turmas,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::prova($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $data = $request->validate([
            'titulo'          => 'sometimes|string|max:255',
            'disciplina'      => 'sometimes|string|max:100',
            'serie'           => 'sometimes|string|max:50',
            'num_questoes'    => 'sometimes|integer|min:1|max:100',
            'num_alternativas'=> 'sometimes|integer|in:3,4,5',
            'nota_maxima'     => 'sometimes|numeric|min:0.5|max:100',
            'tipo_pontuacao'  => ['sometimes', Rule::in(['igual', 'personalizado'])],
            'anular_se_todas' => 'sometimes|boolean',
            'opcao_lingua'    => 'sometimes|boolean',
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'status'          => ['sometimes', Rule::in(['rascunho', 'publicada', 'em_correcao', 'corrigida'])],
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => 'sometimes|array',
            'turma_ids.*'     => 'integer|distinct|exists:turmas,id',
        ]);

        $turmaIds = $data['turma_ids'] ?? null;
        unset($data['turma_ids']);
        if ($turmaIds !== null && ($erro = $this->foraDoEscopo($request->user(), (int) $prova->escola_id, $turmaIds))) {
            return $erro;
        }
        DB::transaction(function () use ($prova, $id, $data, $turmaIds, $request) {
            // Serializa edição das associações e preserva autoria de vínculos mantidos.
            DB::table('provas')->where('id', $id)->lockForUpdate()->first();
            if ($turmaIds !== null) {
                $q = DB::table('prova_turmas')->where('prova_id', $id)
                    ->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $prova->espaco_id));
                $existentes = (clone $q)->pluck('turma_id')->all();
                (clone $q)->whereNotIn('turma_id', $turmaIds)->delete();
                $novas = array_values(array_diff($turmaIds, $existentes));
                if ($novas) {
                    DB::table('prova_turmas')->insert(array_map(fn ($tid) => ['prova_id' => $id, 'turma_id' => $tid]
                        + (Espacos::ativo() ? Espacos::autoria((int) $prova->espaco_id, $request->user()) : []), $novas));
                }
            }
            $prova->update($data);
        });

        return ApiResponse::success($prova->fresh(), null, 200);
    }

    public function publicar(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::prova($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        if ($prova->status !== 'rascunho') {
            return ApiResponse::error('Apenas rascunhos podem ser publicados.', [], 422);
        }

        $prova->update(['status' => 'publicada']);

        return ApiResponse::success(['status' => 'publicada'], null, 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::prova($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        if ($prova->status !== 'rascunho') {
            return ApiResponse::error('Apenas rascunhos podem ser excluídos.', [], 422);
        }

        DB::table('prova_turmas')->where('prova_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $prova->espaco_id))->delete();
        $prova->delete();

        return ApiResponse::success(['excluido' => true]);
    }
}

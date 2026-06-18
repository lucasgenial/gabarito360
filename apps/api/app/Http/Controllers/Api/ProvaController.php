<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProvaController extends Controller
{
    private function escopoEscolaIds(Request $request): ?array
    {
        $usuario = $request->user();

        return match ($usuario->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('escolas')
                ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
                ->join('usuario_escopos', function ($j) use ($usuario) {
                    $j->on('nucleos.id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $usuario->id)
                      ->where('usuario_escopos.escopo_tipo', 'nucleo');
                })
                ->pluck('escolas.id')->all(),
            'dir_escolar', 'coordenador' => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->pluck('escopo_id')->all(),
            'professor' => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->pluck('escopo_id')->all(),
            default => [],
        };
    }

    private function progresso(Prova $p): array
    {
        if ($p->status === 'corrigida') {
            $totalCartoes = DB::table('cartoes')->where('prova_id', $p->id)->count();
            $lidos        = DB::table('cartoes')->where('prova_id', $p->id)->whereIn('status', ['lido', 'ambiguo'])->count();
            $media        = DB::table('notas')->where('prova_id', $p->id)->avg('nota_final');

            return [
                'tipo'          => 'media',
                'media'         => $media !== null ? round((float) $media, 1) : null,
                'cartoes_lidos' => $lidos,
                'cartoes_total' => $totalCartoes,
            ];
        }

        if ($p->status === 'em_correcao') {
            $totalCartoes = DB::table('cartoes')->where('prova_id', $p->id)->count();
            $lidos        = DB::table('cartoes')->where('prova_id', $p->id)->whereIn('status', ['lido', 'ambiguo'])->count();

            return ['tipo' => 'correcao', 'lidos' => $lidos, 'total' => $totalCartoes];
        }

        if ($p->status === 'rascunho') {
            $gabarito    = DB::table('gabaritos')->where('prova_id', $p->id)->first();
            $preenchidas = $gabarito ? DB::table('gabarito_questoes')->where('gabarito_id', $gabarito->id)->count() : 0;

            return ['tipo' => 'gabarito', 'preenchidas' => $preenchidas, 'total' => $p->num_questoes];
        }

        return ['tipo' => 'nenhum'];
    }

    public function index(Request $request): JsonResponse
    {
        $escolaIds = $this->escopoEscolaIds($request);
        $usuario   = $request->user();
        $query     = Prova::query();

        if ($escolaIds !== null) {
            $query->whereIn('escola_id', $escolaIds);
        }

        // Professor só vê provas que criou ou das turmas dele
        if ($usuario->perfil === 'professor') {
            $turmaIds = DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'turma')
                ->pluck('escopo_id');

            $query->where(function ($q) use ($usuario, $turmaIds) {
                $q->where('criado_por', $usuario->id)
                  ->orWhereIn('id', DB::table('prova_turmas')->whereIn('turma_id', $turmaIds)->pluck('prova_id'));
            });
        }

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
            ->map(function (Prova $p) {
                $turmas = DB::table('prova_turmas')
                    ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->all();

                return array_merge($p->toArray(), [
                    'escola_nome' => $p->escola?->nome,
                    'turmas'      => $turmas,
                    'progresso'   => $this->progresso($p),
                ]);
            });

        $baseQ = Prova::query();
        if ($escolaIds !== null) $baseQ->whereIn('escola_id', $escolaIds);

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
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => 'sometimes|array',
            'turma_ids.*'     => 'integer|exists:turmas,id',
        ]);

        $turmaIds = $data['turma_ids'] ?? [];
        unset($data['turma_ids']);

        $data['criado_por'] = $usuario->id;
        $data['status']     = 'rascunho';

        $prova = Prova::create($data);

        if ($turmaIds) {
            $inserts = array_map(fn ($tid) => ['prova_id' => $prova->id, 'turma_id' => $tid], $turmaIds);
            DB::table('prova_turmas')->insert($inserts);
        }

        return ApiResponse::success($prova, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $escolaIds = $this->escopoEscolaIds($request);
        $query     = Prova::with('escola:id,nome');

        if ($escolaIds !== null) {
            $query->whereIn('escola_id', $escolaIds);
        }

        $prova = $query->find($id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = DB::table('prova_turmas')
            ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->where('prova_turmas.prova_id', $id)
            ->select('turmas.id', 'turmas.nome', 'turmas.serie')
            ->get();

        return ApiResponse::success(array_merge($prova->toArray(), [
            'escola_nome' => $prova->escola?->nome,
            'turmas'      => $turmas,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $prova = Prova::find($id);

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
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'status'          => ['sometimes', Rule::in(['rascunho', 'publicada', 'em_correcao', 'corrigida'])],
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => 'sometimes|array',
            'turma_ids.*'     => 'integer|exists:turmas,id',
        ]);

        if (isset($data['turma_ids'])) {
            $turmaIds = $data['turma_ids'];
            unset($data['turma_ids']);

            DB::table('prova_turmas')->where('prova_id', $id)->delete();
            if ($turmaIds) {
                $inserts = array_map(fn ($tid) => ['prova_id' => $id, 'turma_id' => $tid], $turmaIds);
                DB::table('prova_turmas')->insert($inserts);
            }
        }

        $prova->update($data);

        return ApiResponse::success($prova->fresh(), null, 200);
    }

    public function publicar(int $id): JsonResponse
    {
        $prova = Prova::find($id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        if ($prova->status !== 'rascunho') {
            return ApiResponse::error('Apenas rascunhos podem ser publicados.', [], 422);
        }

        $prova->update(['status' => 'publicada']);

        return ApiResponse::success(['status' => 'publicada'], null, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $prova = Prova::find($id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        if ($prova->status !== 'rascunho') {
            return ApiResponse::error('Apenas rascunhos podem ser excluídos.', [], 422);
        }

        DB::table('prova_turmas')->where('prova_id', $id)->delete();
        $prova->delete();

        return ApiResponse::success(['excluido' => true]);
    }
}

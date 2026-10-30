<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\Cabecalho;
use App\Support\Escopo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Http/Controllers/Api/RelatorioConsolidadoController.php
 * Função: Relatórios consolidados de escola, núcleo e rede (MP-034): indicadores, média
 *         por área e comparativo entre as unidades do nível abaixo (turmas da escola,
 *         escolas do núcleo/rede). Notas na escala 0–10 para comparar provas diferentes.
 *         Sem id, usa a unidade do próprio usuário (escola do diretor/coordenador,
 *         núcleo do diretor de núcleo).
 */
class RelatorioConsolidadoController extends Controller
{
    public function escola(Request $request, ?int $id = null): JsonResponse
    {
        $usuario = $request->user();
        $escolas = Escopo::escolaIds($usuario);
        $id ??= $escolas[0] ?? null;
        // A rota exige relatorio.escola (padrão ou extra); aqui só o escopo da pessoa conta
        if (!$id || ($escolas !== null && !in_array($id, $escolas, true))) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $escola = DB::table('escolas')->where('id', $id)->first(['id', 'nome', 'inep', 'nucleo_id']);
        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $notas = $this->notas([$id]);

        return ApiResponse::success(array_merge([
            'nivel'    => 'escola',
            'cabecalho' => Cabecalho::escola((int) $escola->id),
            'unidade'  => ['id' => $escola->id, 'nome' => $escola->nome, 'detalhe' => $escola->inep && $escola->inep !== 'PENDENTE' ? 'INEP ' . $escola->inep : null],
            'grupo'    => 'turma',
            'grupos'   => $this->comparativo($notas, 'turma_id', 'turma_nome',
                DB::table('turmas')->where('escola_id', $id)->where('ativo', true)->orderBy('nome')->pluck('nome', 'id')),
            'destaques' => $this->alunos($notas, 'desc'),
            'atencao'   => $this->alunos($notas, 'asc', true),
        ], $this->base($notas, [$id])));
    }

    public function nucleo(Request $request, ?int $id = null): JsonResponse
    {
        $usuario = $request->user();
        // A rota exige relatorio.nucleo; o admin da rede vê todos, os demais só os núcleos do seu escopo
        $meus = $usuario->perfil === 'admin_rede' ? null
            : DB::table('usuario_escopos')->where('usuario_id', $usuario->id)->where('escopo_tipo', 'nucleo')->pluck('escopo_id')->map(fn ($v) => (int) $v)->all();
        $id ??= $meus[0] ?? null;
        if (!$id || ($meus !== null && !in_array($id, $meus, true))) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        $nucleo = DB::table('nucleos')->leftJoin('redes', 'redes.id', '=', 'nucleos.rede_id')
            ->where('nucleos.id', $id)->first(['nucleos.id', 'nucleos.nome', 'redes.id as rede_id', 'redes.nome as rede']);
        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        $escolaIds = DB::table('escolas')->where('nucleo_id', $id)->pluck('id')->all();
        $notas = $this->notas($escolaIds);

        return ApiResponse::success(array_merge([
            'nivel'   => 'nucleo',
            'cabecalho' => Cabecalho::unidade($nucleo->nome, $nucleo->rede, $nucleo->rede_id ? (int) $nucleo->rede_id : null),
            'unidade' => ['id' => $nucleo->id, 'nome' => $nucleo->nome, 'detalhe' => $nucleo->rede],
            'grupo'   => 'escola',
            'grupos'  => $this->comparativo($notas, 'escola_id', 'escola_nome',
                DB::table('escolas')->whereIn('id', $escolaIds)->orderBy('nome')->pluck('nome', 'id')),
        ], $this->base($notas, $escolaIds)));
    }

    public function rede(Request $request, ?int $id = null): JsonResponse
    {
        if ($request->user()->perfil !== 'admin_rede') {
            return ApiResponse::notFound('Rede não encontrada.');
        }

        $redes = DB::table('redes')->orderBy('nome')->get(['id', 'nome']);
        $id ??= $redes->count() === 1 ? $redes->first()->id : null;
        if (!$id) {
            // Mais de uma rede: devolve a lista para escolher
            return ApiResponse::success(['nivel' => 'rede', 'escolher' => $redes]);
        }

        $rede = $redes->firstWhere('id', $id);
        if (!$rede) {
            return ApiResponse::notFound('Rede não encontrada.');
        }

        $escolaIds = DB::table('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->where('nucleos.rede_id', $id)->pluck('escolas.id')->all();
        $notas = $this->notas($escolaIds);

        return ApiResponse::success(array_merge([
            'nivel'   => 'rede',
            'cabecalho' => Cabecalho::unidade($rede->nome, null, (int) $rede->id),
            'unidade' => ['id' => $rede->id, 'nome' => $rede->nome, 'detalhe' => null],
            'grupo'   => 'escola',
            'grupos'  => $this->comparativo($notas, 'escola_id', 'escola_nome',
                DB::table('escolas')->whereIn('id', $escolaIds)->orderBy('nome')->pluck('nome', 'id')),
            'redes'   => $redes,
        ], $this->base($notas, $escolaIds)));
    }

    /** Notas (escala 0–10) das provas aplicadas nas escolas, com turma/escola/disciplina. */
    private function notas(array $escolaIds): Collection
    {
        if (!$escolaIds) {
            return collect();
        }

        return DB::table('notas')
            ->join('provas', 'provas.id', '=', 'notas.prova_id')
            ->join('turmas', 'turmas.id', '=', 'notas.turma_id')
            ->join('escolas', 'escolas.id', '=', 'turmas.escola_id')
            ->join('alunos', 'alunos.id', '=', 'notas.aluno_id')
            ->whereIn('turmas.escola_id', $escolaIds)
            ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida'])
            ->get([
                'notas.aluno_id', 'alunos.nome as aluno_nome', 'notas.prova_id', 'provas.disciplina',
                'notas.turma_id', 'turmas.nome as turma_nome', 'turmas.escola_id', 'escolas.nome as escola_nome',
                DB::raw('notas.nota_final * 10 / (CASE WHEN provas.nota_maxima > 0.1 THEN provas.nota_maxima ELSE 0.1 END) as nota10'),
            ])
            ->map(function ($n) { $n->nota10 = (float) $n->nota10; return $n; });
    }

    /** Indicadores e média por área, comuns aos três níveis. */
    private function base(Collection $notas, array $escolaIds): array
    {
        $alunos = $escolaIds ? DB::table('alunos')->join('turmas', 'turmas.id', '=', 'alunos.turma_id')
            ->whereIn('turmas.escola_id', $escolaIds)->where('alunos.ativo', true)->count() : 0;
        $provas = $escolaIds ? DB::table('provas')->whereIn('escola_id', $escolaIds)
            ->whereIn('status', ['publicada', 'em_correcao', 'corrigida'])->count() : 0;

        $areas = $notas->groupBy('disciplina')->map(fn ($g, $disc) => [
            'disciplina'    => $disc,
            'media'         => round($g->avg('nota10'), 1),
            'aprovacao_pct' => round($g->where('nota10', '>=', 6)->count() / $g->count() * 100),
            'notas'         => $g->count(),
        ])->sortKeys()->values();

        return [
            'kpis' => [
                'escolas'       => count($escolaIds),
                'alunos'        => $alunos,
                'provas'        => $provas,
                'notas'         => $notas->count(),
                'alunos_com_nota' => $notas->pluck('aluno_id')->unique()->count(),
                'media'         => $notas->isNotEmpty() ? round($notas->avg('nota10'), 1) : null,
                'aprovacao_pct' => $notas->isNotEmpty() ? round($notas->where('nota10', '>=', 6)->count() / $notas->count() * 100) : null,
            ],
            'areas' => $areas,
        ];
    }

    /** Comparativo entre unidades (turmas ou escolas): média geral e por área. */
    private function comparativo(Collection $notas, string $chave, string $rotulo, Collection $todas): array
    {
        $porGrupo = $notas->groupBy($chave);

        return $todas->map(function ($nome, $id) use ($porGrupo) {
            $g = $porGrupo->get($id, collect());

            return [
                'id'            => (int) $id,
                'nome'          => $nome,
                'notas'         => $g->count(),
                'alunos'        => $g->pluck('aluno_id')->unique()->count(),
                'media'         => $g->isNotEmpty() ? round($g->avg('nota10'), 1) : null,
                'aprovacao_pct' => $g->isNotEmpty() ? round($g->where('nota10', '>=', 6)->count() / $g->count() * 100) : null,
                'por_area'      => $g->groupBy('disciplina')->map(fn ($a) => round($a->avg('nota10'), 1))->all(),
            ];
        })->sortByDesc(fn ($g) => $g['media'] ?? -1)->values()->all();
    }

    /** Melhores médias (desc) ou alunos abaixo de 6,0 (asc, só atenção), até 10. */
    private function alunos(Collection $notas, string $ordem, bool $soAtencao = false): array
    {
        $lista = $notas->groupBy('aluno_id')->map(fn ($g) => [
            'id'     => (int) $g->first()->aluno_id,
            'nome'   => $g->first()->aluno_nome,
            'turma'  => $g->first()->turma_nome,
            'provas' => $g->count(),
            'media'  => round($g->avg('nota10'), 1),
        ]);
        if ($soAtencao) {
            $lista = $lista->where('media', '<', 6);
        }

        return ($ordem === 'desc' ? $lista->sortByDesc('media') : $lista->sortBy('media'))->take(10)->values()->all();
    }
}

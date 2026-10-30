<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use App\Support\Escopo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    private function encontrarProva(Request $request, int $provaId): ?Prova
    {
        return Escopo::provaComConteudo($request->user(), $provaId);
    }

    private function metaRede(int $escolaId): float
    {
        $meta = DB::table('escolas')
            ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
            ->join('redes', 'nucleos.rede_id', '=', 'redes.id')
            ->where('escolas.id', $escolaId)
            ->value('redes.meta_media');

        return $meta !== null ? (float) $meta : 7.0;
    }

    private function tabelaAlunos(int $provaId, ?int $turmaId = null): array
    {
        $query = DB::table('notas')
            ->join('alunos', 'alunos.id', '=', 'notas.aluno_id')
            ->join('turmas', 'turmas.id', '=', 'notas.turma_id')
            ->where('notas.prova_id', $provaId);

        if ($turmaId !== null) {
            $query->where('notas.turma_id', $turmaId);
        }

        return $query
            ->select(
                'alunos.id as aluno_id',
                'alunos.nome as aluno_nome',
                'turmas.id as turma_id',
                'turmas.nome as turma_nome',
                'notas.nota_final',
                'notas.status_aprovacao'
            )
            ->orderBy('alunos.nome')
            ->get()
            ->toArray();
    }

    private function kpis(Prova $prova, ?int $turmaId = null): array
    {
        $cartoesQuery = DB::table('cartoes')->where('prova_id', $prova->id);
        $notasQuery   = DB::table('notas')->where('prova_id', $prova->id);

        if ($turmaId !== null) {
            $cartoesQuery->whereIn('aluno_id', DB::table('alunos')->where('turma_id', $turmaId)->pluck('id'));
            $notasQuery->where('turma_id', $turmaId);
        }

        $totalCartoes = (clone $cartoesQuery)->count();
        $lidos        = (clone $cartoesQuery)->where('status', 'lido')->count();
        $ambiguos     = (clone $cartoesQuery)->where('status', 'ambiguo')->count();

        $totalNotas    = (clone $notasQuery)->count();
        $aprovados     = (clone $notasQuery)->where('status_aprovacao', 'aprovado')->count();
        $media         = (clone $notasQuery)->avg('nota_final');
        $maiorNota     = (clone $notasQuery)->max('nota_final');

        return [
            'media'             => $media !== null ? round((float) $media, 1) : 0.0,
            'meta_rede'         => $this->metaRede($prova->escola_id),
            'aprovacao_pct'     => $totalNotas > 0 ? round(($aprovados / $totalNotas) * 100) : 0,
            'aprovados'         => $aprovados,
            'total_alunos'      => $totalNotas,
            'cartoes_lidos'     => $lidos,
            'cartoes_total'     => $totalCartoes,
            'pendencias'        => $ambiguos,
            'maior_nota'        => $maiorNota !== null ? round((float) $maiorNota, 1) : null,
        ];
    }

    public function prova(Request $request, int $id): JsonResponse
    {
        $prova = $this->encontrarProva($request, $id);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = DB::table('prova_turmas')
            ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->where('prova_turmas.prova_id', $id)
            ->pluck('turmas.nome');

        return ApiResponse::success([
            'prova'  => array_merge($prova->toArray(), ['turmas' => $turmas]),
            'kpis'     => $this->kpis($prova),
            'alunos'   => $this->tabelaAlunos($id),
            'questoes' => $this->analiseQuestoes($prova),
        ]);
    }

    public function turmaProva(Request $request, int $turmaId, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turma = DB::table('turmas')->where('id', $turmaId)->first();
        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        return ApiResponse::success([
            'prova'  => $prova,
            'turma'  => $turma,
            'kpis'     => $this->kpis($prova, $turmaId),
            'alunos'   => $this->tabelaAlunos($provaId, $turmaId),
            'questoes' => $this->analiseQuestoes($prova, $turmaId),
        ]);
    }

    /**
     * Análise por questão: % de acerto, distribuição das marcações (A–E/branco) e a
     * alternativa errada mais marcada. Considera só cartões lidos e com aluno; quem
     * marcou Espanhol é comparado com o gabarito de Espanhol (mesma regra da nota).
     */
    private function analiseQuestoes(Prova $prova, ?int $turmaId = null): array
    {
        $gabarito = DB::table('gabaritos')->where('prova_id', $prova->id)->first();
        if (!$gabarito) {
            return [];
        }
        $questoes = DB::table('gabarito_questoes')->where('gabarito_id', $gabarito->id)
            ->orderBy('numero_questao')->get()->keyBy('numero_questao');

        $respostas = DB::table('cartao_respostas as r')
            ->join('cartoes as c', 'c.id', '=', 'r.cartao_id')
            ->join('alunos as a', 'a.id', '=', 'c.aluno_id')
            ->where('c.prova_id', $prova->id)
            ->where('c.status', 'lido')
            ->when($turmaId, fn ($q) => $q->where('a.turma_id', $turmaId))
            ->get(['r.numero_questao', 'r.alternativa', 'c.lingua']);

        $letras = array_slice(['A', 'B', 'C', 'D', 'E'], 0, (int) $prova->num_alternativas);
        $saida  = [];
        foreach ($questoes as $n => $q) {
            $daQuestao = $respostas->where('numero_questao', $n);
            $dist = array_fill_keys($letras, 0) + ['branco' => 0];
            $acertos = 0;
            foreach ($daQuestao as $r) {
                $dist[$r->alternativa ?? 'branco'] = ($dist[$r->alternativa ?? 'branco'] ?? 0) + 1;
                $esperada = ($prova->opcao_lingua && $r->lingua === 'ESP' && !empty($q->alternativa_espanhol))
                    ? $q->alternativa_espanhol : $q->alternativa;
                if (!$q->anulada && $r->alternativa === $esperada) {
                    $acertos++;
                }
            }
            $total = $daQuestao->count();

            // Distrator: alternativa errada mais marcada (considera o gabarito de Inglês/único)
            $erradas = array_diff_key(array_intersect_key($dist, array_flip($letras)), [$q->alternativa => 0]);
            arsort($erradas);
            $distrator = $erradas && reset($erradas) > 0 ? key($erradas) : null;

            $saida[] = [
                'numero'               => (int) $n,
                'gabarito'             => $q->alternativa,
                'gabarito_espanhol'    => $q->alternativa_espanhol ?? null,
                'anulada'              => (bool) $q->anulada,
                'total'                => $total,
                'acertos'              => $acertos,
                'pct'                  => $total > 0 ? round($acertos / $total * 100) : null,
                'distribuicao'         => $dist,
                'distrator'            => $distrator,
            ];
        }

        return $saida;
    }
}

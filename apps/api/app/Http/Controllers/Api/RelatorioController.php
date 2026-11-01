<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use App\Support\Cabecalho;
use App\Support\Escopo;
use App\Support\ConsultasPedagogicas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    private function encontrarProva(Request $request, int $provaId): ?Prova
    {
        return Escopo::provaComConteudo($request->user(), $provaId);
    }

    private function metaRede(ConsultasPedagogicas $consulta, ?int $escolaId): float
    {
        if ($escolaId === null) return 7.0;
        $meta = $consulta->tabela('escolas')
            ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
            ->join('redes', 'nucleos.rede_id', '=', 'redes.id')
            ->where('escolas.id', $escolaId)
            ->value('redes.meta_media');

        return $meta !== null ? (float) $meta : 7.0;
    }

    private function tabelaAlunos(ConsultasPedagogicas $consulta, int $provaId, ?int $turmaId = null): array
    {
        $query = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.id', '=', 'notas.aluno_id')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'turmas.id', '=', 'notas.turma_id')
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

    private function kpis(ConsultasPedagogicas $consulta, Prova $prova, ?int $turmaId = null): array
    {
        $cartoesQuery = $consulta->tabela('cartoes')->where('prova_id', $prova->id);
        $notasQuery   = $consulta->tabela('notas')->where('prova_id', $prova->id);

        if ($turmaId !== null) {
            $cartoesQuery->whereRaw('COALESCE(cartoes.turma_aplicacao_id, (SELECT turma_id FROM alunos WHERE alunos.id = cartoes.aluno_id)) = ?', [$turmaId]);
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
            'meta_rede'         => $this->metaRede($consulta, $prova->escola_id),
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
        $consulta = new ConsultasPedagogicas($request->user());
        $prova = $this->encontrarProva($request, $id);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->where('prova_turmas.prova_id', $id)
            ->pluck('turmas.nome');

        return ApiResponse::success([
            'prova'  => array_merge($prova->toArray(), ['turmas' => $turmas]),
            'cabecalho' => Cabecalho::contexto($request->user(), $prova->escola_id),
            'kpis'     => $this->kpis($consulta, $prova),
            'alunos'   => $this->tabelaAlunos($consulta, $id),
            'questoes' => $this->analiseQuestoes($consulta, $prova),
        ]);
    }

    public function turmaProva(Request $request, int $turmaId, int $provaId): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turma = $this->turmaNoEscopo($request, $turmaId);
        if ($turma && !$consulta->tabela('prova_turmas')->where('prova_id', $provaId)->where('turma_id', $turmaId)->exists()) $turma = null;
        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        return ApiResponse::success([
            'prova'  => $prova,
            'turma'  => $turma,
            'cabecalho' => Cabecalho::contexto($request->user(), $prova->escola_id),
            'kpis'     => $this->kpis($consulta, $prova, $turmaId),
            'alunos'   => $this->tabelaAlunos($consulta, $provaId, $turmaId),
            'questoes' => $this->analiseQuestoes($consulta, $prova, $turmaId),
        ]);
    }

    /**
     * Relatório consolidado da turma em todas as provas: média e aprovação por prova,
     * comparação com as outras turmas da escola e ranking dos alunos.
     */
    public function turma(Request $request, int $turmaId): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $turma = $this->turmaNoEscopo($request, $turmaId);
        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $alunos = $consulta->tabela('alunos')->where(fn ($q) => $q->where('turma_id', $turmaId)
            ->when(\App\Support\EspacoPessoal::doAtor($request->user()), fn ($h) => $h->orWhereIn('id', $consulta->tabela('notas')->where('turma_id', $turmaId)->select('aluno_id'))))->where('ativo', true)
            ->orderBy('nome')->get(['id', 'nome', 'matricula']);
        $provas = $this->provasDaTurma($request, $turmaId);

        $notas = $consulta->tabela('notas')->where('turma_id', $turmaId)->whereIn('prova_id', $provas->pluck('id'))
            ->get(['aluno_id', 'prova_id', 'nota_final', 'acertos', 'total_questoes'])
            ->keyBy(fn ($n) => $n->aluno_id . ':' . $n->prova_id);

        $resumoProvas = $provas->map(function ($p) use ($turmaId, $turma, $notas, $alunos, $consulta) {
            $daProva = $alunos->map(fn ($a) => $notas->get($a->id . ':' . $p->id))->filter();
            $max     = (float) $p->nota_maxima;
            // Média da mesma prova nas outras turmas da escola (comparativo)
            $porTurma = $consulta->tabela('notas')->joinSub($consulta->tabela('turmas'), 'turmas', 'turmas.id', '=', 'notas.turma_id')
                ->where('notas.prova_id', $p->id)->where('turmas.escola_id', $turma->escola_id)
                ->select('turmas.id', 'turmas.nome', DB::raw('ROUND(AVG(notas.nota_final), 2) as media'), DB::raw('COUNT(*) as alunos'))
                ->groupBy('turmas.id', 'turmas.nome')->orderBy('turmas.nome')->get();

            return [
                'id'            => (int) $p->id,
                'disciplina'    => $p->disciplina,
                'titulo'        => $p->titulo,
                'status'        => $p->status,
                'nota_maxima'   => $max,
                'corrigidos'    => $daProva->count(),
                'media'         => $daProva->isNotEmpty() ? round((float) $daProva->avg('nota_final'), 1) : null,
                'aprovacao_pct' => $daProva->isNotEmpty() ? round($daProva->filter(fn ($n) => $n->nota_final >= $max * 0.6)->count() / $daProva->count() * 100) : null,
                'maior'         => $daProva->isNotEmpty() ? (float) $daProva->max('nota_final') : null,
                'menor'         => $daProva->isNotEmpty() ? (float) $daProva->min('nota_final') : null,
                'por_turma'     => $porTurma->map(fn ($t) => ['turma_id' => (int) $t->id, 'turma' => $t->nome, 'media' => round((float) $t->media, 1), 'alunos' => (int) $t->alunos])->all(),
            ];
        })->values();

        $ranking = $alunos->map(function ($a) use ($provas, $notas) {
            $daAluno = [];
            foreach ($provas as $p) {
                $n = $notas->get($a->id . ':' . $p->id);
                $daAluno[$p->id] = $n ? (float) $n->nota_final : null;
            }
            $feitas = array_filter($daAluno, fn ($v) => $v !== null);

            return [
                'id'      => (int) $a->id,
                'nome'    => $a->nome,
                'notas'   => $daAluno,
                'feitas'  => count($feitas),
                'media'   => $feitas ? round(array_sum($feitas) / count($feitas), 1) : null,
            ];
        })->sortByDesc(fn ($a) => [$a['media'] ?? -1, $a['feitas']])->values()
            ->map(function ($a, $i) { $a['posicao'] = $a['media'] !== null ? $i + 1 : null; return $a; });

        $todas = $notas->values();

        return ApiResponse::success([
            'turma'   => $turma,
            'cabecalho' => Cabecalho::contexto($request->user(), $turma->escola_id),
            'kpis'    => [
                'alunos'          => $alunos->count(),
                'provas'          => $provas->count(),
                'media_geral'     => $todas->isNotEmpty() ? round((float) $todas->avg('nota_final'), 1) : null,
                'notas_lancadas'  => $todas->count(),
                'notas_esperadas' => $alunos->count() * $provas->count(),
                'alunos_completos'=> $ranking->where('feitas', $provas->count())->count(),
            ],
            'provas'  => $resumoProvas,
            'ranking' => $ranking,
        ]);
    }

    /** Boletim do aluno: todas as provas da turma, nota, média e posição na turma. */
    public function aluno(Request $request, int $alunoId): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $aluno = $consulta->tabela('alunos')->where('id', $alunoId)->first(['id', 'nome', 'matricula', 'turma_id', 'nome_responsavel']);
        $turma = $aluno ? $this->turmaNoEscopo($request, (int) $aluno->turma_id) : null;
        if (!$aluno || !$turma) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $provas = $this->provasDaTurma($request, (int) $aluno->turma_id);
        $pessoal = \App\Support\EspacoPessoal::doAtor($request->user());
        if ($pessoal) {
            $historicas = Escopo::provas($request->user())->whereIn('provas.id', $consulta->tabela('notas')->where('aluno_id', $alunoId)->select('prova_id'))
                ->whereIn('status', ['publicada', 'em_correcao', 'corrigida'])->get();
            $provas = $provas->concat($historicas)->unique('id')->sortBy('disciplina')->values();
        }
        $linhas = $provas->map(function ($p) use ($aluno, $consulta, $pessoal) {
            $turmaNota = $pessoal ? ($consulta->tabela('notas')->where('prova_id', $p->id)->where('aluno_id', $aluno->id)->value('turma_id') ?? $aluno->turma_id) : $aluno->turma_id;
            $daTurma = $consulta->tabela('notas')->where('prova_id', $p->id)->where('turma_id', $turmaNota)
                ->orderByDesc('nota_final')->get(['aluno_id', 'nota_final', 'acertos', 'total_questoes']);
            $minha = $daTurma->firstWhere('aluno_id', $aluno->id);
            $pos   = $minha ? $daTurma->search(fn ($n) => (float) $n->nota_final === (float) $minha->nota_final) + 1 : null;
            $lingua = $minha ? $consulta->tabela('cartoes')->joinSub($consulta->tabela('notas'), 'notas', 'notas.cartao_id', '=', 'cartoes.id')
                ->where('notas.prova_id', $p->id)->where('notas.aluno_id', $aluno->id)->value('cartoes.lingua') : null;

            return [
                'id'          => (int) $p->id,
                'disciplina'  => $p->disciplina,
                'titulo'      => $p->titulo,
                'data'        => $p->data_aplicacao,
                'nota_maxima' => (float) $p->nota_maxima,
                'nota'        => $minha ? (float) $minha->nota_final : null,
                'acertos'     => $minha->acertos ?? null,
                'total'       => $minha->total_questoes ?? null,
                'lingua'      => $lingua,
                'media_turma' => $daTurma->isNotEmpty() ? round((float) $daTurma->avg('nota_final'), 1) : null,
                'posicao'     => $pos,
                'corrigidos'  => $daTurma->count(),
            ];
        })->values();

        $feitas = $linhas->filter(fn ($l) => $l['nota'] !== null);

        return ApiResponse::success([
            'aluno'  => $aluno,
            'turma'  => $turma,
            'cabecalho' => Cabecalho::contexto($request->user(), $turma->escola_id),
            'provas' => $linhas,
            'kpis'   => [
                'provas'        => $linhas->count(),
                'feitas'        => $feitas->count(),
                'media'         => $feitas->isNotEmpty() ? round((float) $feitas->avg('nota'), 1) : null,
                'media_turma'   => $feitas->isNotEmpty() ? round((float) $feitas->avg('media_turma'), 1) : null,
                'acertos'       => $feitas->sum('acertos'),
                'questoes'      => $feitas->sum('total'),
            ],
        ]);
    }

    /** Turma dentro do escopo do usuário (com o nome da escola), ou null. */
    private function turmaNoEscopo(Request $request, int $turmaId): ?object
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        $ids     = Escopo::turmaIds($usuario);
        if (in_array($usuario->perfil, ['aplicador', 'aluno'], true) || ($ids !== null && !in_array($turmaId, $ids, true))) {
            return null;
        }

        return $consulta->tabela('turmas')->leftJoinSub($consulta->tabela('escolas'), 'escolas', 'escolas.id', '=', 'turmas.escola_id')
            ->where('turmas.id', $turmaId)
            ->first(['turmas.id', 'turmas.nome', 'turmas.serie', 'turmas.escola_id', 'escolas.nome as escola_nome']);
    }

    /** Provas da turma já aplicáveis e visíveis ao usuário, por disciplina. */
    private function provasDaTurma(Request $request, int $turmaId)
    {
        $consulta = new ConsultasPedagogicas($request->user());
        return Escopo::provas($request->user())
            ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'prova_turmas.prova_id', '=', 'provas.id')
            ->where('prova_turmas.turma_id', $turmaId)
            ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida'])
            ->orderBy('provas.disciplina')
            ->get(['provas.id', 'provas.titulo', 'provas.disciplina', 'provas.status', 'provas.data_aplicacao', 'provas.nota_maxima']);
    }

    /**
     * Análise por questão: % de acerto, distribuição das marcações (A–E/branco) e a
     * alternativa errada mais marcada. Considera só cartões lidos e com aluno; quem
     * marcou Espanhol é comparado com o gabarito de Espanhol (mesma regra da nota).
     */
    private function analiseQuestoes(ConsultasPedagogicas $consulta, Prova $prova, ?int $turmaId = null): array
    {
        $gabarito = $consulta->tabela('gabaritos')->where('prova_id', $prova->id)->first();
        if (!$gabarito) {
            return [];
        }
        $questoes = $consulta->tabela('gabarito_questoes')->where('gabarito_id', $gabarito->id)
            ->orderBy('numero_questao')->get()->keyBy('numero_questao');

        $respostas = DB::query()->fromSub($consulta->tabela('cartao_respostas'), 'r')
            ->joinSub($consulta->tabela('cartoes'), 'c', 'c.id', '=', 'r.cartao_id')
            ->joinSub($consulta->tabela('alunos'), 'a', 'a.id', '=', 'c.aluno_id')
            ->where('c.prova_id', $prova->id)
            ->where('c.status', 'lido')
            ->when($turmaId, fn ($q) => $q->whereRaw('COALESCE(c.turma_aplicacao_id, a.turma_id) = ?', [$turmaId]))
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use App\Models\Cartao;
use App\Models\Escola;
use App\Models\Nota;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\Secretaria;
use App\Models\SincronizacaoSeges;
use App\Models\Usuario;
use App\Models\Visita;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\Instituicoes;
use App\Support\ConsultasPedagogicas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function aluno(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'aluno')) return $escolha;

        // Busca o registro de aluno vinculado ao usuário
        $alunoId = Instituicoes::selecionarReferencia($request, 'aluno', $this->referencias($consulta, 'aluno')->pluck('id')->all());

        if (!$alunoId) {
            return Espacos::ativo() ? ApiResponse::notFound('Aluno não encontrado.') : ApiResponse::forbidden('Usuário não vinculado a nenhum aluno.');
        }

        $aluno = $consulta->tabela('alunos')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->joinSub($consulta->tabela('escolas'), 'escolas', 'turmas.escola_id', '=', 'escolas.id')
            ->where('alunos.id', $alunoId)
            ->select('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome as turma', 'turmas.id as turma_id', 'escolas.nome as escola')
            ->first();

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        // KPIs
        $provasRealizadas = $consulta->modelo(Nota::class)->where('aluno_id', $alunoId)->count();
        $minhaMedia       = $consulta->modelo(Nota::class)->where('aluno_id', $alunoId)->avg('nota_final');

        $melhorDisciplina = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('notas.aluno_id', $alunoId)
            ->select('provas.disciplina', DB::raw('ROUND(AVG(notas.nota_final),1) as media'))
            ->groupBy('provas.disciplina')
            ->orderByDesc('media')
            ->first();

        $proximaProva = $consulta->tabela('provas')
            ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'provas.id', '=', 'prova_turmas.prova_id')
            ->where('prova_turmas.turma_id', $aluno->turma_id)
            ->where('provas.data_aplicacao', '>=', now()->toDateString())
            ->whereIn('provas.status', ['publicada', 'rascunho'])
            ->orderBy('provas.data_aplicacao')
            ->select('provas.id', 'provas.disciplina', 'provas.data_aplicacao')
            ->first();

        // Resumo e última nota por disciplina em duas projeções SQL do mesmo recorte.
        $resumoNotas = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('notas.aluno_id', $alunoId)
            ->select(
                'provas.disciplina',
                DB::raw('MAX(provas.data_aplicacao) as ultima_data'),
                DB::raw('ROUND(AVG(notas.nota_final),1) as media_bimestral')
            )
            ->groupBy('provas.disciplina');
        $ordemNotas = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'provas.id', '=', 'notas.prova_id')
            ->where('notas.aluno_id', $alunoId)
            ->select('provas.disciplina', 'notas.nota_final')
            // Preserva a ordenação anterior, inclusive o empate sem critério adicional.
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY provas.disciplina ORDER BY provas.data_aplicacao DESC) as posicao');
        $ultimasNotas = DB::query()->fromSub($ordemNotas, 'ordem_notas')->where('posicao', 1);
        $notas = DB::query()->fromSub($resumoNotas, 'resumo_notas')
            ->leftJoinSub($ultimasNotas, 'ultimas_notas', fn ($j) => $j->whereRaw(
                '(ultimas_notas.disciplina = resumo_notas.disciplina OR (ultimas_notas.disciplina IS NULL AND resumo_notas.disciplina IS NULL))'
            ))
            ->select('resumo_notas.*', 'ultimas_notas.nota_final as ultima_nota')
            ->orderBy('resumo_notas.disciplina')
            ->get()
            ->map(function ($n) {
                // Tendência simples: comparar última nota com média
                $diff = $n->ultima_nota - $n->media_bimestral;
                $n->tendencia = $diff > 0.3 ? 'up' : ($diff < -0.3 ? 'down' : 'flat');
                return $n;
            });

        $bimestres = $this->bimestres($consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('notas.aluno_id', $alunoId));

        // Próximas provas da turma
        $proximasProvas = $consulta->tabela('provas')
            ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'provas.id', '=', 'prova_turmas.prova_id')
            ->leftJoin('usuarios', 'provas.criado_por', '=', 'usuarios.id')
            ->where('prova_turmas.turma_id', $aluno->turma_id)
            ->where('provas.data_aplicacao', '>=', now()->toDateString())
            ->whereIn('provas.status', ['publicada', 'rascunho'])
            ->orderBy('provas.data_aplicacao')
            ->limit(4)
            ->select('provas.id', 'provas.disciplina', 'provas.data_aplicacao', 'usuarios.nome as professor')
            ->get();

        return ApiResponse::success([
            'aluno' => [
                'id'     => $aluno->id,
                'nome'   => $aluno->nome,
                'turma'  => $aluno->turma,
                'escola' => $aluno->escola,
            ],
            'kpis' => [
                'provas_realizadas'  => $provasRealizadas,
                'minha_media'        => $minhaMedia ? round($minhaMedia, 1) : null,
                'melhor_disciplina'  => $melhorDisciplina?->disciplina,
                'melhor_media'       => $melhorDisciplina?->media,
                'proxima_prova_data' => $proximaProva?->data_aplicacao,
                'proxima_prova_disc' => $proximaProva?->disciplina,
            ],
            'notas'          => $notas,
            'bimestres'      => $bimestres,
            'proximas_provas' => $proximasProvas,
        ]);
    }

    public function professor(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();

        // Turmas do professor via escopos
        $turmaIds = collect(Instituicoes::referencias($usuario, 'turma'));

        if (Espacos::ativo()) $turmaIds = collect(Escopo::turmaIds($usuario) ?? []);

        // KPIs — provas visíveis: criadas por ele ou das turmas em que está vinculado
        $provaIds     = Escopo::provas($usuario)->pluck('provas.id');
        $minhasProvas = $provaIds->count();

        $cartoesCorrigir = $consulta->tabela('cartoes')
            ->whereIn('prova_id', $provaIds)
            ->whereIn('status', ['pendente', 'ambiguo'])
            ->count();

        $minhasTurmas = $turmaIds->count();

        $mediaTurmas = $consulta->tabela('notas')
            ->whereIn('notas.turma_id', $turmaIds)
            ->avg('notas.nota_final');

        // Disciplina principal (a mais usada nas provas do professor)
        $disciplinaPrincipal = $consulta->tabela('provas')
            ->whereIn('id', $provaIds)
            ->select('disciplina', DB::raw('COUNT(*) as total'))
            ->groupBy('disciplina')
            ->orderByDesc('total')
            ->value('disciplina');

        // Tabela de provas do professor (mais recentes)
        $provas = Escopo::provas($usuario)
            ->orderByRaw("CASE status WHEN 'em_correcao' THEN 1 WHEN 'publicada' THEN 2 WHEN 'rascunho' THEN 3 ELSE 4 END")
            ->orderByDesc('data_aplicacao')
            ->limit(12)
            ->get(['id', 'titulo', 'disciplina', 'status', 'data_aplicacao']);
        [$nomes, $listas, $totais, $lidos] = $this->dadosProvas($consulta, $provas);
        $provas = $provas->map(function ($p) use ($turmaIds, $nomes, $listas, $totais, $lidos) {
                $todas = $listas->get($p->id, collect());
                $minhas = $todas->whereIn('id', $turmaIds->all());
                return [
                    'id'             => $p->id,
                    'titulo'         => $p->titulo,
                    'disciplina'     => $p->disciplina,
                    'turma'          => ($nomes->get($p->id)?->pluck('nome')->implode(', ')) ?: '—',
                    // Cartões por turma: só as turmas do professor (todas, se ele criou a prova)
                    'turmas_lista'   => (Espacos::ativo() ? $todas : ($minhas->isNotEmpty() ? $minhas : $todas))
                        ->map(fn ($t) => ['id' => (int) $t->id, 'nome' => $t->nome])->values()->all(),
                    'data_aplicacao' => $p->data_aplicacao,
                    'total_alunos'   => (int) ($totais->get($p->id)?->total ?? 0),
                    'cartoes_lidos'  => (int) ($lidos->get($p->id)?->total ?? 0),
                    'status'         => $p->status,
                ];
            });

        // Ranking das turmas do professor
        $top5 = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'notas.aluno_id', '=', 'alunos.id')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'notas.turma_id', '=', 'turmas.id')
            ->whereIn('notas.turma_id', $turmaIds)
            ->select('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome as turma', DB::raw('ROUND(AVG(notas.nota_final),1) as media'))
            ->groupBy('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome')
            ->orderByDesc('media')
            ->limit(5)
            ->get();

        $bottom3 = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'notas.aluno_id', '=', 'alunos.id')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'notas.turma_id', '=', 'turmas.id')
            ->whereIn('notas.turma_id', $turmaIds)
            ->select('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome as turma', DB::raw('ROUND(AVG(notas.nota_final),1) as media'))
            ->groupBy('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome')
            ->orderBy('media')
            ->limit(3)
            ->get();

        // Alunos que precisam de atenção (abaixo de 6, com menor média)
        $alunosAtencao = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'notas.aluno_id', '=', 'alunos.id')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'notas.turma_id', '=', 'turmas.id')
            ->whereIn('notas.turma_id', $turmaIds)
            ->select('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome as turma', DB::raw('ROUND(AVG(notas.nota_final),1) as media'))
            ->groupBy('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome')
            ->havingRaw('AVG(notas.nota_final) < 6')
            ->orderBy('media')
            ->limit(3)
            ->get();

        return ApiResponse::success([
            'professor' => [
                'nome'               => $usuario->nome,
                'disciplina'         => $disciplinaPrincipal,
                'total_turmas'       => $minhasTurmas,
                'escola_nome'        => \App\Support\EspacoPessoal::doAtor($usuario) ? null : $usuario->escola_nome,
                'espaco_nome'        => \App\Support\EspacoPessoal::doAtor($usuario)
                    ? \App\Support\ContextoVinculo::resolver($usuario)->espaco->nome : null,
            ],
            'kpis' => [
                'minhas_provas'     => $minhasProvas,
                'cartoes_corrigir'  => $cartoesCorrigir,
                'minhas_turmas'     => $minhasTurmas,
                'media_turmas'      => $mediaTurmas ? round($mediaTurmas, 1) : null,
            ],
            'provas'         => $provas,
            'ranking_top5'   => $top5,
            'ranking_bottom3' => $bottom3,
            'alunos_atencao' => $alunosAtencao,
        ]);
    }

    /** Projeções limitadas às provas exibidas; nenhum SQL no map de cada prova. */
    private function dadosProvas(ConsultasPedagogicas $consulta, \Illuminate\Support\Collection $provas, ?string $statusCartao = 'lido', bool $comListas = true): array
    {
        if ($provas->isEmpty()) return [collect(), collect(), collect(), collect()];
        $ids = $provas->pluck('id')->all();
        // O texto original não tem ORDER BY; a lista usa a collation/ordenação do banco.
        $vinculos = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->whereIn('prova_turmas.prova_id', $ids);
        $nomes = (clone $vinculos)->get(['prova_turmas.prova_id', 'turmas.nome'])->groupBy('prova_id');
        $listas = $comListas
            ? (clone $vinculos)->orderBy('turmas.nome')->get(['prova_turmas.prova_id', 'turmas.id', 'turmas.nome'])->groupBy('prova_id')
            : collect();
        $totais = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
            ->whereIn('prova_turmas.prova_id', $ids)->where('alunos.ativo', true)
            ->groupBy('prova_turmas.prova_id')->select('prova_turmas.prova_id')->selectRaw('COUNT(*) as total')->get()->keyBy('prova_id');
        $cartoes = $consulta->tabela('cartoes')->whereIn('prova_id', $ids);
        if ($statusCartao !== null) $cartoes->where('status', $statusCartao);
        $cartoes = $cartoes->groupBy('prova_id')->select('prova_id')->selectRaw('COUNT(*) as total')->get()->keyBy('prova_id');
        return [$nomes, $listas, $totais, $cartoes];
    }

    /** Mantém os quatro períodos de três meses do contrato existente. */
    private function bimestres(\Illuminate\Database\Query\Builder $notas): array
    {
        $inicio = now()->startOfYear();
        $notas->whereYear('provas.data_aplicacao', $inicio->year);
        for ($bim = 1; $bim <= 4; $bim++) {
            $de = $inicio->copy()->addMonths(($bim - 1) * 3)->toDateString();
            $ate = $inicio->copy()->addMonths($bim * 3)->toDateString();
            $notas->selectRaw('AVG(CASE WHEN provas.data_aplicacao >= ? AND provas.data_aplicacao < ? THEN notas.nota_final ELSE NULL END) as periodo_'.$bim, [$de, $ate]);
        }
        $medias = $notas->first();
        $bimestres = [];
        for ($bim = 1; $bim <= 4; $bim++) {
            $media = $medias->{'periodo_'.$bim};
            // Preserva o valor bruto e a mesma condição do avg() anterior, inclusive DECIMAL zero.
            $bimestres[] = ['bimestre' => $bim, 'label' => "{$bim}º bim.", 'media' => $media ? round($media, 1) : null];
        }
        return $bimestres;
    }

    public function coordenador(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        $opcoes = $this->referencias($consulta, 'escola');
        if ($escolha = $this->escolha($request, $consulta, 'escola', $opcoes)) return $escolha;

        $escolaId = Instituicoes::selecionarReferencia($request, 'escola', $opcoes->pluck('id')->all());

        if (!$escolaId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhuma escola.');
        }

        $escola = $consulta->modelo(Escola::class)->findOrFail($escolaId);

        // KPIs
        $provasAndamento = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->whereIn('status', ['publicada', 'em_correcao'])
            ->count();

        $cartoesTotal = $consulta->tabela('cartoes')
            ->joinSub($consulta->tabela('provas'), 'provas', 'cartoes.prova_id', '=', 'provas.id')
            ->where('provas.escola_id', $escolaId)
            ->whereIn('cartoes.status', ['pendente', 'ambiguo'])
            ->count();

        $alunosBaixaMedia = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('provas.escola_id', $escolaId)
            ->select('notas.aluno_id')
            ->groupBy('notas.aluno_id')
            ->havingRaw('AVG(notas.nota_final) < 6')
            ->get()
            ->count();

        $proximasProvas = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->where('data_aplicacao', '>=', now()->toDateString())
            ->where('data_aplicacao', '<=', now()->addDays(7)->toDateString())
            ->count();

        // Tabela de provas em andamento
        $provas = $consulta->tabela('provas')
            ->where('provas.escola_id', $escolaId)
            ->whereIn('provas.status', ['publicada', 'em_correcao'])
            ->leftJoin('usuarios', 'provas.criado_por', '=', 'usuarios.id')
            ->select(
                'provas.id',
                'provas.titulo',
                'provas.disciplina',
                'provas.status',
                'provas.data_aplicacao',
                'usuarios.nome as professor_nome'
            )
            ->orderByDesc('provas.updated_at')
            ->limit(8)
            ->get();
        $provas = $this->complementarProvasCoordenador($consulta, $provas);

        // Alunos em atenção (os 5 com menor média)
        $alunosAtencao = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->joinSub($consulta->tabela('alunos'), 'alunos', 'notas.aluno_id', '=', 'alunos.id')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'notas.turma_id', '=', 'turmas.id')
            ->where('provas.escola_id', $escolaId)
            ->select(
                'alunos.id',
                'alunos.nome',
                'alunos.avatar_id',
                'turmas.nome as turma',
                'provas.disciplina',
                DB::raw('ROUND(AVG(notas.nota_final), 1) as media')
            )
            ->groupBy('alunos.id', 'alunos.nome', 'alunos.avatar_id', 'turmas.nome', 'provas.disciplina')
            ->havingRaw('AVG(notas.nota_final) < 6')
            ->orderBy('media')
            ->limit(5)
            ->get();

        // Agenda: próximas provas da semana
        $agenda = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->where('data_aplicacao', '>=', now()->toDateString())
            ->where('data_aplicacao', '<=', now()->addDays(7)->toDateString())
            ->orderBy('data_aplicacao')
            ->limit(4)
            ->get(['id', 'titulo', 'disciplina', 'data_aplicacao', 'status']);

        return ApiResponse::success([
            'escola' => ['id' => $escola->id, 'nome' => $escola->nome],
            'kpis' => [
                'provas_andamento'    => $provasAndamento,
                'cartoes_pendentes'   => $cartoesTotal,
                'alunos_baixa_media'  => $alunosBaixaMedia,
                'proximas_provas'     => $proximasProvas,
            ],
            'provas'         => $provas,
            'alunos_atencao' => $alunosAtencao,
            'agenda'         => $agenda,
        ]);
    }

    /** Quatro consultas auxiliares no máximo, sem materializar alunos/cartões inteiros. */
    private function complementarProvasCoordenador(ConsultasPedagogicas $consulta, \Illuminate\Support\Collection $provas): \Illuminate\Support\Collection
    {
        if ($provas->isEmpty()) return $provas;
        [$nomes, $listas, $totais, $lidos] = $this->dadosProvas($consulta, $provas);
        return $provas->map(fn ($p) => [
            'id' => $p->id, 'disciplina' => $p->disciplina, 'titulo' => $p->titulo,
            'professor' => $p->professor_nome,
            'turmas' => ($nomes->get($p->id)?->pluck('nome')->implode(', ')) ?: '—',
            'turmas_lista' => ($listas->get($p->id) ?? collect())->map(fn ($t) => ['id'=>(int)$t->id,'nome'=>$t->nome])->values()->all(),
            'total_alunos' => (int) ($totais->get($p->id)?->total ?? 0),
            'cartoes_lidos' => (int) ($lidos->get($p->id)?->total ?? 0),
            'status' => $p->status, 'data_aplicacao' => $p->data_aplicacao,
        ]);
    }

    public function dirEscolar(Request $request): JsonResponse
    {
        if ($request->user()->perfil === 'vice_dir_escolar') {
            foreach (['turma.ver', 'aluno.ver', 'prova.ver', 'resultado.ver', 'relatorio.escola', 'usuario.ver'] as $chave) abort_unless(\App\Support\Permissao::pode($request->user(), $chave), 403);
        }
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'escola')) return $escolha;

        $escolaId = Instituicoes::selecionarReferencia($request, 'escola', $this->referencias($consulta, 'escola')->pluck('id')->all());

        if (!$escolaId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhuma escola.');
        }

        $escola = $consulta->modelo(Escola::class)->findOrFail($escolaId);

        // KPIs
        $turmasAtivas = $consulta->tabela('turmas')
            ->where('escola_id', $escolaId)
            ->where('ativo', true)
            ->count();

        $totalAlunos = $consulta->tabela('alunos')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->where('turmas.escola_id', $escolaId)
            ->where('alunos.ativo', true)
            ->count();

        $provasAplicadas = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->whereIn('status', ['corrigida', 'em_correcao', 'publicada'])
            ->count();

        $mediaEscola = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('provas.escola_id', $escolaId)
            ->avg('notas.nota_final');

        // Tabela por turma
        $turmas = $consulta->tabela('turmas')
            ->where('turmas.escola_id', $escolaId)
            ->where('turmas.ativo', true)
            ->select('turmas.id', 'turmas.nome', 'turmas.serie')
            ->orderBy('turmas.serie')
            ->orderBy('turmas.nome')
            ->get();
        $turmaIds = $turmas->pluck('id')->all();
        $alunosPorTurma = $consulta->tabela('alunos')->whereIn('turma_id', $turmaIds)->where('ativo', true)
            ->select('turma_id')->selectRaw('COUNT(*) as total')->groupBy('turma_id')->get()->keyBy('turma_id');
        $mediasPorTurma = $consulta->tabela('notas')->whereIn('turma_id', $turmaIds)
            ->select('turma_id')->selectRaw('AVG(nota_final) as media')->groupBy('turma_id')->get()->keyBy('turma_id');
        $ordemProvas = $consulta->tabela('provas')
            ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'provas.id', '=', 'prova_turmas.prova_id')
            ->whereIn('prova_turmas.turma_id', $turmaIds)->whereIn('provas.status', ['corrigida', 'em_correcao', 'publicada'])
            ->select('prova_turmas.turma_id', 'provas.disciplina')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY prova_turmas.turma_id ORDER BY provas.data_aplicacao DESC) as posicao');
        $ultimasProvas = DB::query()->fromSub($ordemProvas, 'ordem_provas')->where('posicao', 1)->get()->keyBy('turma_id');
        $professores = $this->professoresTurmas($consulta, $turmaIds);
        $turmas = $turmas->map(function ($turma) use ($alunosPorTurma, $mediasPorTurma, $ultimasProvas, $professores) {
                $mediaRaw = $mediasPorTurma->get($turma->id)?->media;
                $media = $mediaRaw ? round($mediaRaw, 1) : null;

                $status = match (true) {
                    $media === null     => 'sem_dados',
                    $media >= 7.5       => 'destaque',
                    $media >= 7.0       => 'bom_desempenho',
                    $media >= 6.5       => 'em_dia',
                    $media >= 6.0       => 'monitorar',
                    $media >= 5.0       => 'atencao',
                    default             => 'plano_apoio',
                };

                return [
                    'id'           => $turma->id,
                    'nome'         => $turma->nome,
                    'serie'        => $turma->serie,
                    'total_alunos' => (int) ($alunosPorTurma->get($turma->id)?->total ?? 0),
                    'ultima_prova' => $ultimasProvas->get($turma->id)?->disciplina,
                    'professor'    => $professores->get($turma->id)?->nome,
                    'media'        => $media,
                    'status'       => $status,
                ];
            });

        // Equipe da escola
        if (\App\Support\ContextoVinculo::presente($request->user())) {
            $equipe = \App\Support\GestaoVinculos::pessoas($request->user(), $escolaId)->limit(6)->get();
        } else {
            $equipe = DB::table('usuarios')
                ->join('usuario_escopos', 'usuarios.id', '=', 'usuario_escopos.usuario_id')
                ->where('usuario_escopos.escopo_tipo', 'escola')
                ->where('usuario_escopos.escopo_id', $escolaId)
                ->where('usuarios.ativo', true)
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.perfil')
                ->limit(6)
                ->get();
        }

        // Próximas provas
        $proximasProvas = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->where('data_aplicacao', '>=', now()->toDateString())
            ->orderBy('data_aplicacao')
            ->limit(4)
            ->get(['id', 'titulo', 'disciplina', 'data_aplicacao', 'status']);

        return ApiResponse::success([
            'escola' => [
                'id'     => $escola->id,
                'nome'   => $escola->nome,
                'inep'   => $escola->inep,
                'ativo'  => $escola->ativo,
                'cidade' => $escola->cidade,
                'uf'     => $escola->uf,
                'etapas' => $escola->etapas ?? [],
                'turnos' => $escola->turnos ?? [],
            ],
            'kpis' => [
                'turmas_ativas'   => $turmasAtivas,
                'total_alunos'    => $totalAlunos,
                'provas_aplicadas' => $provasAplicadas,
                'media_escola'    => $mediaEscola ? round($mediaEscola, 1) : null,
            ],
            'turmas'        => $turmas,
            'equipe'        => $equipe,
            'proximas_provas' => $proximasProvas,
        ]);
    }

    /** Primeiro docente de cada turma sem repetir a consulta de gestão por turma. */
    private function professoresTurmas(ConsultasPedagogicas $consulta, array $turmas): \Illuminate\Support\Collection
    {
        if (!$turmas) return collect();
        if (\App\Support\ContextoVinculo::presente($consulta->ator)) {
            $q = \App\Support\GestaoVinculos::pessoas($consulta->ator)
                ->where('contextual.perfil_vinculo', 'professor')
                ->join('vinculo_turmas as docente_turma', fn ($j) => $j->on('docente_turma.vinculo_id', '=', 'contextual.vinculo_id')
                    ->on('docente_turma.espaco_id', '=', 'contextual.espaco_id'))
                ->joinSub($consulta->tabela('turmas'), 'turma_docente', fn ($j) => $j->on('turma_docente.id', '=', 'docente_turma.turma_id')
                    ->on('turma_docente.espaco_id', '=', 'contextual.espaco_id')->on('turma_docente.escola_id', '=', 'contextual.escola_id'))
                ->whereIn('docente_turma.turma_id', $turmas)
                ->select('docente_turma.turma_id', 'usuarios.nome')
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY docente_turma.turma_id ORDER BY usuarios.id) as posicao');
        } else {
            $q = DB::table('usuarios')->join('usuario_escopos', 'usuarios.id', '=', 'usuario_escopos.usuario_id')
                ->where('usuario_escopos.escopo_tipo', 'turma')->whereIn('usuario_escopos.escopo_id', $turmas)
                ->where('usuarios.perfil', 'professor')
                ->select('usuario_escopos.escopo_id as turma_id', 'usuarios.nome')
                // O ramo legado não definia desempate de professores; não cria um novo critério.
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY usuario_escopos.escopo_id) as posicao');
        }
        return DB::query()->fromSub($q, 'professores_turmas')->where('posicao', 1)->get()->keyBy('turma_id');
    }

    public function dirNucleo(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'nucleo')) return $escolha;

        // Descobrir o núcleo do diretor via escopos
        $nucleoId = Instituicoes::selecionarReferencia($request, 'nucleo', $this->referencias($consulta, 'nucleo')->pluck('id')->all());

        if (!$nucleoId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhum núcleo.');
        }

        $nucleo = $consulta->modelo(Nucleo::class)->findOrFail($nucleoId);

        // IDs das escolas do núcleo
        $escolaIds = $consulta->modelo(Escola::class)->where('nucleo_id', $nucleoId)->pluck('id');

        // KPIs
        $totalEscolas = $escolaIds->count();
        $totalAlunos  = $consulta->modelo(Aluno::class)->whereHas('turma', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->where('ativo', true)
            ->count();

        $provasRealizadas = $consulta->modelo(Prova::class)->whereIn('escola_id', $escolaIds)
            ->whereIn('status', ['corrigida', 'em_correcao', 'publicada'])
            ->count();

        $mediaNucleo = $consulta->modelo(Nota::class)->whereHas('prova', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->avg('nota_final');

        // Comparativo de escolas
        $escolas = $consulta->tabela('escolas')
            ->leftJoinSub($consulta->paraJuncao('turmas', ['escola_id', 'ativo']), 'turmas', function ($j) {
                $j->on('turmas.escola_id', '=', 'escolas.id')->where('turmas.ativo', true);
            })
            ->leftJoinSub($consulta->paraJuncao('alunos', ['turma_id', 'ativo']), 'alunos', function ($j) {
                $j->on('alunos.turma_id', '=', 'turmas.id')->where('alunos.ativo', true);
            })
            ->leftJoinSub($consulta->paraJuncao('provas', ['escola_id']), 'provas', 'provas.escola_id', '=', 'escolas.id')
            ->leftJoinSub($consulta->paraJuncao('notas', ['prova_id', 'nota_final']), 'notas', 'notas.prova_id', '=', 'provas.id')
            ->whereIn('escolas.id', $escolaIds)
            ->select(
                'escolas.id',
                'escolas.nome',
                DB::raw('COUNT(DISTINCT turmas.id) as total_turmas'),
                DB::raw('COUNT(DISTINCT alunos.id) as total_alunos'),
                DB::raw('ROUND(AVG(notas.nota_final), 1) as media')
            )
            ->groupBy('escolas.id', 'escolas.nome')
            ->orderByDesc('media')
            ->get()
            ->map(function ($e) {
                $media = $e->media;
                $e->status = match (true) {
                    $media === null => 'sem_dados',
                    $media >= 8.0 => 'destaque',
                    $media >= 7.0 => 'acima_meta',
                    $media >= 6.5 => 'monitoramento',
                    $media >= 5.0 => 'atencao',
                    default        => 'abaixo_meta',
                };
                return $e;
            });

        $bimestres = $this->bimestres($consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->whereIn('provas.escola_id', $escolaIds));

        // Próximas visitas
        $visitas = $consulta->modelo(Visita::class)->where('nucleo_id', $nucleoId)
            ->where('data_visita', '>=', now()->toDateString())
            ->orderBy('data_visita')
            ->limit(3)
            ->with('escola:id,nome')
            ->get(['id', 'escola_id', 'data_visita', 'tipo', 'urgencia']);

        return ApiResponse::success([
            'nucleo' => ['id' => $nucleo->id, 'nome' => $nucleo->nome],
            'kpis' => [
                'total_escolas'    => $totalEscolas,
                'total_alunos'     => $totalAlunos,
                'provas_realizadas' => $provasRealizadas,
                'media_nucleo'     => $mediaNucleo ? round($mediaNucleo, 1) : null,
                'meta_rede'        => 7.0,
            ],
            'escolas'   => $escolas,
            'bimestres' => $bimestres,
            'visitas'   => $visitas,
        ]);
    }

    public function admin(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $totalEscolas   = $consulta->modelo(Escola::class)->count();
        $totalAtivas    = $consulta->modelo(Escola::class)->where('ativo', true)->count();
        $totalAlunos    = $consulta->modelo(Aluno::class)->where('ativo', true)->count();

        $provasMes = $consulta->modelo(Prova::class)->whereMonth('data_aplicacao', now()->month)
            ->whereYear('data_aplicacao', now()->year)
            ->count();

        $mediaRede = $consulta->modelo(Nota::class)->avg('nota_final');

        $top5 = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->joinSub($consulta->tabela('escolas'), 'escolas', 'provas.escola_id', '=', 'escolas.id')
            ->select('escolas.id', 'escolas.nome', DB::raw('ROUND(AVG(notas.nota_final), 1) as media'))
            ->groupBy('escolas.id', 'escolas.nome')
            ->orderByDesc('media')
            ->limit(5)
            ->get();

        $metaRede = 7.0;
        $escolasAbaixo = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->joinSub($consulta->tabela('escolas'), 'escolas', 'provas.escola_id', '=', 'escolas.id')
            ->select('escolas.id')
            ->groupBy('escolas.id')
            ->havingRaw('AVG(notas.nota_final) < ?', [$metaRede])
            ->get()
            ->count();

        $cartoesAmbiguos = $consulta->modelo(Cartao::class)->where('status', 'ambiguo')->count();

        $ultimaSync = $consulta->modelo(SincronizacaoSeges::class)->latest('iniciado_em')->first(); // Sem destino válido, o registro não entra no modo novo.
        $segesAtraso = null;
        if ($ultimaSync && $ultimaSync->duracao_minutos !== null) {
            $segesAtraso = $ultimaSync->duracao_minutos;
        }

        if (\App\Support\ContextoVinculo::presente($request->user())) {
            $ultimosAcessos = \App\Support\GestaoVinculos::pessoas($request->user())->whereNotNull('usuarios.ultimo_acesso')->orderByDesc('usuarios.ultimo_acesso')->limit(5)->get();
        } else {
            $ultimosAcessos = $consulta->modelo(Usuario::class)->whereNotNull('ultimo_acesso')
                ->where('ativo', true)
                ->orderByDesc('ultimo_acesso')
                ->limit(5)
                ->get(['id', 'nome', 'perfil', 'escola_nome', 'ultimo_acesso']);
        }

        // Rede(s) do admin: a do escopo dele ou, sem escopo, todas
        $redeIds = collect(Instituicoes::referencias($request->user(), 'rede'));
        $redes   = $consulta->tabela('redes')->when($redeIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $redeIds))->orderBy('nome')->get(['id', 'nome', 'tipo']);

        return ApiResponse::success([
            'redes' => $redes,
            'kpis' => [
                'total_escolas'     => $totalEscolas,
                'total_escolas_ativas' => $totalAtivas,
                'total_alunos'      => $totalAlunos,
                'provas_mes'        => $provasMes,
                'media_rede'        => $mediaRede ? round($mediaRede, 1) : null,
                'meta_rede'         => $metaRede,
            ],
            'top5_escolas'   => $top5,
            'alertas' => [
                'cartoes_ambiguos'   => $cartoesAmbiguos,
                'escolas_abaixo_meta' => $escolasAbaixo,
                'seges_atraso_min'   => $segesAtraso,
            ],
            'ultimos_acessos' => $ultimosAcessos,
        ]);
    }

    public function secretaria(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'secretaria')) return $escolha;

        $secretariaId = Instituicoes::selecionarReferencia($request, 'secretaria', $this->referencias($consulta, 'secretaria')->pluck('id')->all());

        if (!$secretariaId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhuma secretaria.');
        }

        $secretaria = Secretaria::findOrFail($secretariaId);

        $redeIds = $consulta->tabela('redes')->where('secretaria_id', $secretariaId)->pluck('id');

        $totalRedes = $redeIds->count();

        $escolaIds = $consulta->tabela('escolas')
            ->joinSub($consulta->tabela('nucleos'), 'nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
            ->whereIn('nucleos.rede_id', $redeIds)
            ->pluck('escolas.id');

        $totalAlunos = $consulta->modelo(Aluno::class)->whereHas('turma', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->where('ativo', true)
            ->count();

        $provasRealizadas = $consulta->modelo(Prova::class)->whereIn('escola_id', $escolaIds)
            ->whereIn('status', ['corrigida', 'em_correcao', 'publicada'])
            ->count();

        $mediaSecretaria = $consulta->modelo(Nota::class)->whereHas('prova', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->avg('nota_final');

        // Agregações independentes por rede: a quantidade de escolas/alunos não pondera notas.
        $escolasPorRede = $consulta->tabela('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->whereIn('nucleos.rede_id', $redeIds)->select('nucleos.rede_id')->selectRaw('COUNT(*) as total')
            ->groupBy('nucleos.rede_id')->get()->keyBy('rede_id');
        $escolasRedes = $consulta->tabela('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->whereIn('nucleos.rede_id', $redeIds)->select('escolas.id', 'nucleos.rede_id');
        $alunosPorRede = $consulta->tabela('alunos')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'turmas.id', '=', 'alunos.turma_id')
            ->joinSub(clone $escolasRedes, 'escolas_redes', 'escolas_redes.id', '=', 'turmas.escola_id')
            ->where('turmas.ativo', true)->where('alunos.ativo', true)
            ->select('escolas_redes.rede_id')->selectRaw('COUNT(*) as total')->groupBy('escolas_redes.rede_id')->get()->keyBy('rede_id');
        $mediasPorRede = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'provas.id', '=', 'notas.prova_id')
            ->joinSub(clone $escolasRedes, 'escolas_redes', 'escolas_redes.id', '=', 'provas.escola_id')
            ->select('escolas_redes.rede_id')->selectRaw('AVG(notas.nota_final) as media')->groupBy('escolas_redes.rede_id')->get()->keyBy('rede_id');
        $redes = $consulta->tabela('redes')->whereIn('redes.id', $redeIds)->get(['id', 'nome', 'modalidade'])
            ->map(function ($rede) use ($escolasPorRede, $alunosPorRede, $mediasPorRede) {
                $rede->total_escolas = (int) ($escolasPorRede->get($rede->id)?->total ?? 0);
                $rede->total_alunos = (int) ($alunosPorRede->get($rede->id)?->total ?? 0);
                $media = $mediasPorRede->get($rede->id)?->media;
                $rede->media = $media === null ? null : round((float) $media, 1);
                return $rede;
            })->sortByDesc('media')->values();

        return ApiResponse::success([
            'secretaria' => ['id' => $secretaria->id, 'nome' => $secretaria->nome],
            'kpis' => [
                'total_redes'       => $totalRedes,
                'total_alunos'      => $totalAlunos,
                'provas_realizadas' => $provasRealizadas,
                'media_secretaria'  => $mediaSecretaria ? round($mediaSecretaria, 1) : null,
            ],
            'redes' => $redes,
        ]);
    }

    public function aplicador(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'escola')) return $escolha;

        $escolaId = Instituicoes::selecionarReferencia($request, 'escola', $this->referencias($consulta, 'escola')->pluck('id')->all());

        if (!$escolaId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhuma escola.');
        }

        $escola = $consulta->modelo(Escola::class)->findOrFail($escolaId);

        $hoje = now()->toDateString();

        $provasHoje = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->where('data_aplicacao', $hoje)
            ->get(['id', 'titulo', 'disciplina', 'status']);
        [$nomes, , $totais, $enviados] = $this->dadosProvas($consulta, $provasHoje, statusCartao: null, comListas: false);
        $provasHoje = $provasHoje->map(function ($p) use ($nomes, $totais, $enviados) {
                return [
                    'id'               => $p->id,
                    'titulo'           => $p->titulo,
                    'disciplina'       => $p->disciplina,
                    'turmas'           => ($nomes->get($p->id)?->pluck('nome')->implode(', ')) ?: '—',
                    'total_alunos'     => (int) ($totais->get($p->id)?->total ?? 0),
                    'cartoes_enviados' => (int) ($enviados->get($p->id)?->total ?? 0),
                ];
            });

        return ApiResponse::success([
            'escola' => ['id' => $escola->id, 'nome' => $escola->nome],
            'kpis' => [
                'provas_hoje'      => $provasHoje->count(),
                'cartoes_enviados' => $provasHoje->sum('cartoes_enviados'),
            ],
            'provas_hoje' => $provasHoje,
        ]);
    }

    private function referencias(ConsultasPedagogicas $consulta, string $tipo)
    {
        $ids = Instituicoes::referencias($consulta->ator, $tipo);
        $tabela = ['escola' => 'escolas', 'nucleo' => 'nucleos', 'aluno' => 'alunos', 'secretaria' => 'secretarias'][$tipo];
        $q = $tipo === 'secretaria' ? DB::table($tabela) : $consulta->tabela($tabela);
        return $q->whereIn('id', $ids)->orderBy('nome')->get(['id', 'nome']);
    }

    private function escolha(Request $request, ConsultasPedagogicas $consulta, string $tipo, ?\Illuminate\Support\Collection $opcoes = null): ?JsonResponse
    {
        if (!Espacos::ativo() || $request->has($tipo.'_id')) return null;
        $opcoes ??= $this->referencias($consulta, $tipo);
        return $opcoes->count() > 1
            ? ApiResponse::error('Selecione o contexto da consulta.', ['selecao' => ['campo' => $tipo.'_id', 'opcoes' => $opcoes]], 422)
            : null;
    }
}

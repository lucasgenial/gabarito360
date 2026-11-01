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

        // Tabela de notas por disciplina
        $notas = $consulta->tabela('notas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
            ->where('notas.aluno_id', $alunoId)
            ->select(
                'provas.disciplina',
                DB::raw('MAX(provas.data_aplicacao) as ultima_data'),
                DB::raw('ROUND(AVG(notas.nota_final),1) as media_bimestral'),
                DB::raw('NULL as ultima_nota')
            )
            ->groupBy('provas.disciplina')
            ->orderBy('provas.disciplina')
            ->get()
            ->map(function ($n) use ($alunoId, $consulta) {
                $n->ultima_nota = $consulta->tabela('notas')->joinSub($consulta->tabela('provas'), 'provas', 'provas.id', '=', 'notas.prova_id')
                    ->where('notas.aluno_id', $alunoId)->where('provas.disciplina', $n->disciplina)->orderByDesc('provas.data_aplicacao')->value('notas.nota_final');
                // Tendência simples: comparar última nota com média
                $diff = $n->ultima_nota - $n->media_bimestral;
                $n->tendencia = $diff > 0.3 ? 'up' : ($diff < -0.3 ? 'down' : 'flat');
                return $n;
            });

        // Evolução bimestral
        $ano = now()->year;
        $bimestres = [];
        for ($bim = 1; $bim <= 4; $bim++) {
            $mesInicio = ($bim - 1) * 3 + 1;
            $mesFim    = $bim * 3;
            $media = $consulta->tabela('notas')
                ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
                ->where('notas.aluno_id', $alunoId)
                ->whereYear('provas.data_aplicacao', $ano)
                ->whereMonth('provas.data_aplicacao', '>=', $mesInicio)
                ->whereMonth('provas.data_aplicacao', '<=', $mesFim)
                ->avg('notas.nota_final');

            $bimestres[] = [
                'bimestre' => $bim,
                'label'    => "{$bim}º bim.",
                'media'    => $media ? round($media, 1) : null,
            ];
        }

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
            ->get(['id', 'titulo', 'disciplina', 'status', 'data_aplicacao'])
            ->map(function ($p) use ($turmaIds, $consulta) {
                $turmas = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->implode(', ');

                $totalAlunos = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->where('alunos.ativo', true)
                    ->count();

                return [
                    'id'             => $p->id,
                    'titulo'         => $p->titulo,
                    'disciplina'     => $p->disciplina,
                    'turma'          => $turmas ?: '—',
                    // Cartões por turma: só as turmas do professor (todas, se ele criou a prova)
                    'turmas_lista'   => $this->turmasDaProva($consulta, $p->id, $turmaIds->all()),
                    'data_aplicacao' => $p->data_aplicacao,
                    'total_alunos'   => $totalAlunos,
                    'cartoes_lidos'  => $consulta->tabela('cartoes')->where('prova_id', $p->id)->where('status', 'lido')->count(),
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

    /** Turmas vinculadas à prova [{id, nome}], opcionalmente limitadas a $somente (vazio = todas). */
    private function turmasDaProva(ConsultasPedagogicas $consulta, int $provaId, array $somente = []): array
    {
        $todas = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->where('prova_turmas.prova_id', $provaId)
            ->orderBy('turmas.nome')
            ->get(['turmas.id', 'turmas.nome']);

        $minhas = $todas->whereIn('id', $somente);

        return (Espacos::ativo() ? $todas : ($minhas->isNotEmpty() ? $minhas : $todas))
            ->map(fn ($t) => ['id' => (int) $t->id, 'nome' => $t->nome])->values()->all();
    }

    public function coordenador(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        if ($escolha = $this->escolha($request, $consulta, 'escola')) return $escolha;

        $escolaId = Instituicoes::selecionarReferencia($request, 'escola', $this->referencias($consulta, 'escola')->pluck('id')->all());

        if (!$escolaId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhuma escola.');
        }

        $escola = $consulta->modelo(Escola::class)->findOrFail($escolaId);

        // KPIs
        $provasAndamento = $consulta->modelo(Prova::class)->where('escola_id', $escolaId)
            ->whereIn('status', ['publicada', 'em_correcao'])
            ->count();

        $cartoesAmbiguos = $consulta->tabela('cartoes')
            ->joinSub($consulta->tabela('provas'), 'provas', 'cartoes.prova_id', '=', 'provas.id')
            ->where('provas.escola_id', $escolaId)
            ->where('cartoes.status', 'ambiguo')
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
            ->get()
            ->map(function ($p) use ($consulta) {
                $turmas = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->implode(', ');

                $totalAlunos = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->where('alunos.ativo', true)
                    ->count();

                $cartoesLidos = $consulta->tabela('cartoes')
                    ->where('prova_id', $p->id)
                    ->where('status', 'lido')
                    ->count();

                return [
                    'id'             => $p->id,
                    'disciplina'     => $p->disciplina,
                    'titulo'         => $p->titulo,
                    'professor'      => $p->professor_nome,
                    'turmas'         => $turmas ?: '—',
                    'turmas_lista'   => $this->turmasDaProva($consulta, $p->id),
                    'total_alunos'   => $totalAlunos,
                    'cartoes_lidos'  => $cartoesLidos,
                    'status'         => $p->status,
                    'data_aplicacao' => $p->data_aplicacao,
                ];
            });

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

    public function dirEscolar(Request $request): JsonResponse
    {
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
            ->get()
            ->map(function ($turma) use ($consulta, $request) {
                $totalAlunos = $consulta->tabela('alunos')
                    ->where('turma_id', $turma->id)
                    ->where('ativo', true)
                    ->count();

                $mediaRaw = $consulta->tabela('notas')
                    ->where('turma_id', $turma->id)
                    ->avg('nota_final');
                $media = $mediaRaw ? round($mediaRaw, 1) : null;

                $ultimaProva = $consulta->tabela('provas')
                    ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'provas.id', '=', 'prova_turmas.prova_id')
                    ->where('prova_turmas.turma_id', $turma->id)
                    ->whereIn('provas.status', ['corrigida', 'em_correcao', 'publicada'])
                    ->orderByDesc('provas.data_aplicacao')
                    ->value('provas.disciplina');

                if (\App\Support\ContextoVinculo::presente($request->user())) {
                    $professor = \App\Support\GestaoVinculos::pessoas($request->user(), turma: (int) $turma->id)->orderBy('usuarios.id')->value('usuarios.nome');
                } else {
                    $professor = DB::table('usuarios')
                        ->join('usuario_escopos', 'usuarios.id', '=', 'usuario_escopos.usuario_id')
                        ->where('usuario_escopos.escopo_tipo', 'turma')
                        ->where('usuario_escopos.escopo_id', $turma->id)
                        ->where('usuarios.perfil', 'professor')
                        ->value('usuarios.nome');
                }

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
                    'total_alunos' => $totalAlunos,
                    'ultima_prova' => $ultimaProva,
                    'professor'    => $professor,
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
            ->leftJoinSub($consulta->tabela('turmas'), 'turmas', function ($j) {
                $j->on('turmas.escola_id', '=', 'escolas.id')->where('turmas.ativo', true);
            })
            ->leftJoinSub($consulta->tabela('alunos'), 'alunos', function ($j) {
                $j->on('alunos.turma_id', '=', 'turmas.id')->where('alunos.ativo', true);
            })
            ->leftJoinSub($consulta->tabela('provas'), 'provas', 'provas.escola_id', '=', 'escolas.id')
            ->leftJoinSub($consulta->tabela('notas'), 'notas', 'notas.prova_id', '=', 'provas.id')
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

        // Gráfico bimestral (média por bimestre do ano corrente)
        $ano = now()->year;
        $bimestres = [];
        for ($bim = 1; $bim <= 4; $bim++) {
            $mesInicio = ($bim - 1) * 3 + 1;
            $mesFim    = $bim * 3;
            $media = $consulta->tabela('notas')
                ->joinSub($consulta->tabela('provas'), 'provas', 'notas.prova_id', '=', 'provas.id')
                ->whereIn('provas.escola_id', $escolaIds)
                ->whereYear('provas.data_aplicacao', $ano)
                ->whereMonth('provas.data_aplicacao', '>=', $mesInicio)
                ->whereMonth('provas.data_aplicacao', '<=', $mesFim)
                ->avg('notas.nota_final');

            $bimestres[] = [
                'bimestre' => $bim,
                'label'    => "{$bim}º bim.",
                'media'    => $media ? round($media, 1) : null,
            ];
        }

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

        // Agregações independentes evitam multiplicar notas pelo número de alunos das escolas.
        $redes = $consulta->tabela('redes')->whereIn('redes.id', $redeIds)->get(['id', 'nome', 'modalidade'])
            ->map(function ($rede) use ($consulta) {
                $escolas = $consulta->tabela('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
                    ->where('nucleos.rede_id', $rede->id)->pluck('escolas.id');
                $rede->total_escolas = $escolas->count();
                $rede->total_alunos = $consulta->tabela('alunos')->joinSub($consulta->tabela('turmas'), 'turmas', 'turmas.id', '=', 'alunos.turma_id')
                    ->whereIn('turmas.escola_id', $escolas)->where('turmas.ativo', true)->where('alunos.ativo', true)->count();
                $media = $consulta->tabela('notas')->joinSub($consulta->tabela('provas'), 'provas', 'provas.id', '=', 'notas.prova_id')
                    ->whereIn('provas.escola_id', $escolas)->avg('notas.nota_final');
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
            ->get(['id', 'titulo', 'disciplina', 'status'])
            ->map(function ($p) use ($consulta) {
                $turmas = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->implode(', ');

                $totalAlunos = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->where('alunos.ativo', true)
                    ->count();

                $cartoesEnviados = $consulta->tabela('cartoes')
                    ->where('prova_id', $p->id)
                    ->count();

                return [
                    'id'               => $p->id,
                    'titulo'           => $p->titulo,
                    'disciplina'       => $p->disciplina,
                    'turmas'           => $turmas ?: '—',
                    'total_alunos'     => $totalAlunos,
                    'cartoes_enviados' => $cartoesEnviados,
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

    private function escolha(Request $request, ConsultasPedagogicas $consulta, string $tipo): ?JsonResponse
    {
        if (!Espacos::ativo() || $request->has($tipo.'_id')) return null;
        $opcoes = $this->referencias($consulta, $tipo);
        return $opcoes->count() > 1
            ? ApiResponse::error('Selecione o contexto da consulta.', ['selecao' => ['campo' => $tipo.'_id', 'opcoes' => $opcoes]], 422)
            : null;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\Escopo;
use App\Support\ConsultasPedagogicas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Http/Controllers/Api/AcompanhamentoTurmaController.php
 * Função: Acompanhamento da correção por turma (MP-033/038, mockup
 *         acompanhar-correcao-turma.html): todas as provas da turma × alunos, com
 *         quem já teve o cartão lido, quem falta, ambíguos e notas.
 */
class AcompanhamentoTurmaController extends Controller
{
    public function show(Request $request, int $turmaId): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $usuario = $request->user();
        $turmas  = Escopo::turmaIds($usuario);
        if (in_array($usuario->perfil, ['aplicador', 'aluno'], true)
            || ($turmas !== null && !in_array($turmaId, $turmas, true))) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $turma = $consulta->tabela('turmas')
            ->leftJoinSub($consulta->tabela('escolas'), 'escolas', 'escolas.id', '=', 'turmas.escola_id')
            ->where('turmas.id', $turmaId)
            ->first(['turmas.id', 'turmas.nome', 'turmas.serie', 'turmas.turno', 'turmas.ano_letivo', 'escolas.nome as escola_nome']);
        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $alunos = $consulta->tabela('alunos')
            ->where('turma_id', $turmaId)->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula']);
        $alunoIds = $alunos->pluck('id');

        // Provas aplicáveis da turma (visíveis ao usuário: o professor vê as da turma dele)
        $provas = Escopo::provas($usuario)
            ->joinSub($consulta->tabela('prova_turmas'), 'prova_turmas', 'prova_turmas.prova_id', '=', 'provas.id')
            ->where('prova_turmas.turma_id', $turmaId)
            ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida'])
            ->orderBy('provas.disciplina')
            ->get(['provas.id', 'provas.titulo', 'provas.disciplina', 'provas.status', 'provas.data_aplicacao', 'provas.nota_maxima']);
        $provaIds = $provas->pluck('id');

        // Cartões dos alunos da turma nessas provas (mais recente por aluno/prova)
        $cartoes = $consulta->tabela('cartoes')
            ->leftJoinSub($consulta->tabela('notas'), 'notas', 'notas.cartao_id', '=', 'cartoes.id')
            ->whereIn('cartoes.prova_id', $provaIds)
            ->whereIn('cartoes.aluno_id', $alunoIds)
            ->orderBy('cartoes.id')
            ->get(['cartoes.id', 'cartoes.prova_id', 'cartoes.aluno_id', 'cartoes.status', 'notas.nota_final', 'notas.acertos', 'notas.total_questoes'])
            ->keyBy(fn ($c) => $c->aluno_id . ':' . $c->prova_id);

        // Cartões sem aluno nas provas da turma (QR ilegível / cartão em branco): precisam de vínculo
        $semAluno = $consulta->tabela('cartoes')->whereIn('prova_id', $provaIds)->whereNull('aluno_id')
            ->select('prova_id', DB::raw('COUNT(*) as total'))->groupBy('prova_id')->pluck('total', 'prova_id');

        $resumoProvas = $provas->map(function ($p) use ($alunos, $cartoes, $semAluno) {
            $daProva = $alunos->map(fn ($a) => $cartoes->get($a->id . ':' . $p->id))->filter();
            $notas   = $daProva->pluck('nota_final')->filter(fn ($n) => $n !== null);

            return [
                'id'             => (int) $p->id,
                'titulo'         => $p->titulo,
                'disciplina'     => $p->disciplina,
                'status'         => $p->status,
                'data_aplicacao' => $p->data_aplicacao,
                'nota_maxima'    => (float) $p->nota_maxima,
                'lidos'          => $daProva->where('status', 'lido')->count(),
                'ambiguos'       => $daProva->where('status', 'ambiguo')->count(),
                'faltam'         => $alunos->count() - $daProva->count(),
                'sem_aluno'      => (int) ($semAluno[$p->id] ?? 0),
                'media'          => $notas->isNotEmpty() ? round((float) $notas->avg(), 1) : null,
            ];
        })->values();

        $linhas = $alunos->map(function ($a) use ($provas, $cartoes) {
            $celulas = [];
            foreach ($provas as $p) {
                $c = $cartoes->get($a->id . ':' . $p->id);
                $celulas[$p->id] = $c ? [
                    'cartao_id'  => (int) $c->id,
                    'status'     => $c->status,
                    'nota_final' => $c->nota_final !== null ? (float) $c->nota_final : null,
                    'acertos'    => $c->acertos,
                    'total'      => $c->total_questoes,
                ] : null;
            }

            return ['id' => (int) $a->id, 'nome' => $a->nome, 'matricula' => $a->matricula, 'celulas' => $celulas];
        })->values();

        $esperados = $alunos->count() * $provas->count();
        $lidos     = $resumoProvas->sum('lidos');

        return ApiResponse::success([
            'turma'   => $turma,
            'resumo'  => [
                'alunos'    => $alunos->count(),
                'provas'    => $provas->count(),
                'esperados' => $esperados,
                'lidos'     => $lidos,
                'ambiguos'  => $resumoProvas->sum('ambiguos'),
                'faltam'    => $resumoProvas->sum('faltam'),
                'sem_aluno' => $resumoProvas->sum('sem_aluno'),
            ],
            'provas'  => $resumoProvas,
            'alunos'  => $linhas,
        ]);
    }
}

<?php

namespace App\Services\Omr;

use App\Models\Prova;
use App\Models\Usuario;
use App\Support\Escopo;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;

/** Autorização específica para agregados da mesma prova; não libera registros individuais. */
class EstatisticasQuestoes
{
    public function calcular(Usuario $ator, Prova $prova): array
    {
        abort_unless(!in_array($ator->perfil, ['aluno', 'aplicador']) && Permissao::pode($ator, 'relatorio.prova')
            && Escopo::provaComConteudo($ator, $prova->id), 403);
        $notas = DB::table('notas as n')->join('cartoes as c', 'c.id', '=', 'n.cartao_id')
            ->join('alunos as a', 'a.id', '=', 'n.aluno_id')
            ->join('turmas as t', 't.id', '=', 'n.turma_id')
            ->where('n.prova_id', $prova->id)->where('c.prova_id', $prova->id)
            ->where('n.espaco_id', $prova->espaco_id)->where('c.espaco_id', $prova->espaco_id)
            ->where('a.espaco_id', $prova->espaco_id)->where('t.espaco_id', $prova->espaco_id)
            ->where('t.escola_id', $prova->escola_id)->whereColumn('c.aluno_id', 'n.aluno_id')
            ->whereRaw('n.turma_id = COALESCE(c.turma_aplicacao_id, a.turma_id)')
            ->where('c.status', 'lido')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('prova_turmas as pt')
                ->where('pt.prova_id', $prova->id)->where('pt.espaco_id', $prova->espaco_id)->whereColumn('pt.turma_id', 'n.turma_id'))
            ->orderByDesc('n.id')->get(['n.id', 'n.aluno_id', 'n.turma_id', 'n.cartao_id', 'c.lingua'])->unique('aluno_id');
        $questoes = DB::table('gabarito_questoes as q')->join('gabaritos as g', 'g.id', '=', 'q.gabarito_id')
            ->where('g.prova_id', $prova->id)->where('g.espaco_id', $prova->espaco_id)->where('q.espaco_id', $prova->espaco_id)->get(['q.*']);
        $respostas = DB::table('cartao_respostas')->whereIn('cartao_id', $notas->pluck('cartao_id'))
            ->where('espaco_id', $prova->espaco_id)->get()->groupBy('cartao_id')->map(fn ($rows) => $rows->keyBy('numero_questao'));
        $turmas = $notas->groupBy('turma_id');
        $resultado = [];
        foreach ($questoes as $q) {
            $geral = ['acertos' => 0, 'total' => $notas->count(), 'percentual' => null]; $porTurma = [];
            foreach ($turmas as $id => $grupo) $porTurma[$id] = ['acertos' => 0, 'total' => $grupo->count(), 'percentual' => null];
            if (!$q->anulada) {
                foreach ($notas as $n) {
                    $r = ($respostas[$n->cartao_id] ?? collect())->get($q->numero_questao);
                    $esperada = $prova->opcao_lingua && $n->lingua === 'ESP' && !empty($q->alternativa_espanhol) ? $q->alternativa_espanhol : $q->alternativa;
                    if ($r && !$r->ambigua && $r->alternativa !== null && $r->alternativa === $esperada) {
                        $geral['acertos']++; $porTurma[$n->turma_id]['acertos']++;
                    }
                }
                if ($geral['total']) $geral['percentual'] = round(100 * $geral['acertos'] / $geral['total'], 1);
                foreach ($porTurma as &$v) $v['percentual'] = round(100 * $v['acertos'] / $v['total'], 1);
                unset($v);
            }
            $resultado[$q->numero_questao] = ['geral' => $geral, 'turmas' => $porTurma];
        }
        return $resultado;
    }
}

<?php

namespace App\Services\Omr;

use App\Models\Prova;
use App\Models\Usuario;
use App\Support\ContextoCorrecao;
use App\Support\Espacos;
use Illuminate\Support\Facades\DB;

/** Progresso acadêmico por aluno; fotos e tentativas são medidas independentes. */
final class ProgressoCorrecao
{
    public static function calcular(Prova $prova, ?Usuario $ator = null): array
    {
        $alunos = ContextoCorrecao::alunos($prova, $ator)->where('alunos.ativo', true)->distinct()->pluck('alunos.id');
        $base = ContextoCorrecao::filtrar(DB::table('cartoes'), $prova->espaco_id, 'cartoes.espaco_id')->where('cartoes.prova_id', $prova->id);
        if ($ator && Espacos::ativo()) {
            $base->whereIn('cartoes.id', ContextoCorrecao::cartoes($ator)->select('cartoes.id'));
        }
        $cartoes = (clone $base)->leftJoin('notas', function ($j) {
            $j->on('notas.cartao_id', '=', 'cartoes.id')->on('notas.prova_id', '=', 'cartoes.prova_id')->on('notas.aluno_id', '=', 'cartoes.aluno_id');
            if (Espacos::ativo()) {
                $j->on('notas.espaco_id', '=', 'cartoes.espaco_id');
            }
        })->select('cartoes.id', 'cartoes.aluno_id', 'cartoes.status', 'notas.id as nota_id')->get();
        $envios = DB::table('cartao_envios')->whereIn('cartao_id', $cartoes->pluck('id'))->pluck('status', 'cartao_id');
        $porAluno = $cartoes->groupBy('aluno_id');
        $r = ['esperados' => $alunos->count(), 'concluidos' => 0, 'sem_cartao' => 0, 'em_processamento' => 0, 'alunos_revisao' => 0, 'falhas' => 0];
        foreach ($alunos as $aluno) {
            $cs = $porAluno->get($aluno, collect());
            // Nova proposta/foto não invalida um resultado vigente já confirmado.
            if ($cs->contains(fn ($c) => $c->status === 'lido' && $c->nota_id !== null)) {
                $r['concluidos']++;
            } elseif ($cs->contains('status', 'ambiguo')) {
                $r['alunos_revisao']++;
            } elseif ($cs->contains(fn ($c) => in_array($envios[$c->id] ?? null, ['na_fila', 'processando'], true))) {
                $r['em_processamento']++;
            } elseif ($cs->isNotEmpty()) {
                $r['falhas']++;
            } else {
                $r['sem_cartao']++;
            }
        }
        $r['cartoes_recebidos'] = $cartoes->count();
        $r['nao_identificados'] = $cartoes->whereNull('aluno_id')->count();
        $r['percentual_concluido'] = $r['esperados'] > 0 ? min($r['concluidos'] === $r['esperados'] ? 100 : 99.99, round(100 * $r['concluidos'] / $r['esperados'], 2)) : null;
        $r['concluida'] = $r['esperados'] > 0 && $r['concluidos'] === $r['esperados'];

        return $r;
    }
}

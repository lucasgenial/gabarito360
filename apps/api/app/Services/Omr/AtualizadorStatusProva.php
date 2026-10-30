<?php

namespace App\Services\Omr;

use App\Models\Prova;
use Illuminate\Support\Facades\DB;

class AtualizadorStatusProva
{
    public static function atualizar(Prova $prova): void
    {
        $total = DB::table('cartoes')->where('prova_id', $prova->id)->count();

        if ($total === 0) {
            // Sem cartões (ex.: o único foi movido para a prova do QR): volta a "publicada"
            if (in_array($prova->status, ['em_correcao', 'corrigida'], true)) {
                $prova->update(['status' => 'publicada']);
            }
            return;
        }

        $lidos = DB::table('cartoes')->where('prova_id', $prova->id)->where('status', 'lido')->count();

        // "Todos os cartões" (RN) = um cartão por aluno ativo das turmas da prova.
        // Sem isso a prova virava "corrigida" logo após o 1º cartão lido.
        $esperados = DB::table('alunos')
            ->join('prova_turmas', 'prova_turmas.turma_id', '=', 'alunos.turma_id')
            ->where('prova_turmas.prova_id', $prova->id)
            ->where('alunos.ativo', true)
            ->count();

        if ($lidos === $total && $total >= $esperados) {
            if ($prova->status !== 'corrigida') {
                $prova->update(['status' => 'corrigida']);
            }
            return;
        }

        if (in_array($prova->status, ['publicada', 'corrigida'], true)) {
            $prova->update(['status' => 'em_correcao']);
        }
    }
}

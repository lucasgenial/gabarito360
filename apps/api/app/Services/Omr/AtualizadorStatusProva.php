<?php

namespace App\Services\Omr;

use App\Models\Prova;
use App\Support\ContextoCorrecao;
use App\Support\Espacos;
use Illuminate\Support\Facades\DB;

class AtualizadorStatusProva
{
    public static function atualizar(Prova $prova): void
    {
        if (Espacos::ativo() && !ContextoCorrecao::provaValida($prova)) return;
        $total = ContextoCorrecao::filtrar(DB::table('cartoes'), $prova->espaco_id)->where('prova_id', $prova->id)->count();

        if ($total === 0) {
            // Sem cartões (ex.: o único foi movido para a prova do QR): volta a "publicada"
            if (in_array($prova->status, ['em_correcao', 'corrigida'], true)) {
                $prova->update(['status' => 'publicada']);
            }
            return;
        }

        if (ProgressoCorrecao::calcular($prova)['concluida']) {
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

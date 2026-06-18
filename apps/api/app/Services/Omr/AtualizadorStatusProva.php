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
            return;
        }

        $lidos = DB::table('cartoes')->where('prova_id', $prova->id)->where('status', 'lido')->count();

        if ($lidos === $total) {
            if ($prova->status !== 'corrigida') {
                $prova->update(['status' => 'corrigida']);
            }
            return;
        }

        if ($prova->status === 'publicada') {
            $prova->update(['status' => 'em_correcao']);
        }
    }
}

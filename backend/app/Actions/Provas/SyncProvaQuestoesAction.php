<?php

namespace App\Actions\Provas;

use App\Enums\QuestaoStatus;
use App\Models\Prova;
use Illuminate\Support\Facades\DB;

class SyncProvaQuestoesAction
{
    /**
     * Garante que a prova tenha questões 1..quantidade_questoes (cria as
     * faltantes). A redução abaixo do máximo existente é bloqueada pelo
     * ProvaConfigurationValidator, então aqui só crescemos.
     */
    public function execute(Prova $prova): void
    {
        DB::transaction(function () use ($prova): void {
            $prova->refresh();
            $total = (int) $prova->quantidade_questoes;
            $existentes = $prova->questoes()->pluck('numero')->all();
            $peso = $this->peso($prova, $total);

            $novas = [];
            for ($numero = 1; $numero <= $total; $numero++) {
                if (! in_array($numero, $existentes, true)) {
                    $novas[] = [
                        'numero' => $numero,
                        'status' => QuestaoStatus::ACTIVE->value,
                        'peso_padrao' => $peso,
                    ];
                }
            }

            if ($novas !== []) {
                $prova->questoes()->createMany($novas);
            }
        });
    }

    private function peso(Prova $prova, int $total): float
    {
        $padrao = $prova->padrao ?? [];
        $iguais = ($padrao['pontuacao'] ?? 'iguais') === 'iguais';

        return $iguais && $total > 0
            ? round(((float) $prova->valor_total) / $total, 4)
            : 1.0;
    }
}

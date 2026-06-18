<?php

namespace App\Jobs;

use App\Models\Cartao;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\OmrDriverInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessarCartaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LIMIAR_CONFIANCA = 0.90;

    public function __construct(public int $cartaoId) {}

    public function handle(OmrDriverInterface $driver): void
    {
        $cartao = Cartao::find($this->cartaoId);
        if (!$cartao) {
            return;
        }

        $prova = $cartao->prova;

        $caminho = Storage::disk('public')->path($cartao->imagem_url);
        $leituras = $driver->processar($caminho, $prova->num_questoes, $prova->num_alternativas);

        DB::table('cartao_respostas')->where('cartao_id', $cartao->id)->delete();

        $rows               = [];
        $somaConfianca      = 0.0;
        $temAmbiguaPendente = false;

        foreach ($leituras as $l) {
            $ambigua = $l['ambigua'] || $l['confianca'] < self::LIMIAR_CONFIANCA;

            // Quando a prova está configurada para anular questões com marcação dupla,
            // a divergência é resolvida automaticamente como questão em branco, sem
            // exigir intervenção manual do professor/coordenador.
            if ($ambigua && $l['multiplas_marcacoes'] && $prova->anular_se_todas) {
                $ambigua = false;
                $l['alternativa'] = null;
            }

            if ($ambigua) {
                $temAmbiguaPendente = true;
            }

            $somaConfianca += $l['confianca'];

            $rows[] = [
                'cartao_id'               => $cartao->id,
                'numero_questao'          => $l['numero_questao'],
                'alternativa'             => $ambigua ? null : $l['alternativa'],
                'confianca'               => $l['confianca'],
                'ambigua'                 => $ambigua,
                'alternativas_detectadas' => json_encode($l['alternativas_detectadas']),
            ];
        }

        if ($rows) {
            DB::table('cartao_respostas')->insert($rows);
        }

        $confiancaGeral = count($leituras) ? $somaConfianca / count($leituras) : 0;
        $status         = $temAmbiguaPendente ? 'ambiguo' : 'lido';

        $cartao->update([
            'status'          => $status,
            'confianca_geral' => $confiancaGeral,
        ]);

        if ($status === 'lido') {
            CalculadoraNota::calcular($cartao);
        }

        AtualizadorStatusProva::atualizar($prova);
    }
}

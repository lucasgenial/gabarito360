<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use Illuminate\Support\Facades\DB;

class CalculadoraNota
{
    private const PERCENTUAL_APROVACAO = 0.6;

    /**
     * Calcula (ou recalcula) a nota de um cartão já lido (sem ambiguidades pendentes)
     * comparando as respostas detectadas com o gabarito oficial da prova.
     *
     * Questões anuladas no gabarito não entram no total considerado.
     */
    public static function calcular(Cartao $cartao): void
    {
        if (!$cartao->aluno_id) {
            return;
        }

        $prova = $cartao->prova;

        $gabarito = DB::table('gabaritos')->where('prova_id', $prova->id)->first();
        if (!$gabarito) {
            return;
        }

        $questoesGabarito = DB::table('gabarito_questoes')
            ->where('gabarito_id', $gabarito->id)
            ->get()
            ->keyBy('numero_questao');

        $respostas = DB::table('cartao_respostas')
            ->where('cartao_id', $cartao->id)
            ->get()
            ->keyBy('numero_questao');

        $totalQuestoes = 0;
        $acertos       = 0;

        foreach ($questoesGabarito as $numero => $questao) {
            if ($questao->anulada) {
                continue;
            }

            $totalQuestoes++;

            $resposta = $respostas->get($numero);
            if ($resposta && $resposta->alternativa === $questao->alternativa) {
                $acertos++;
            }
        }

        $notaFinal = $totalQuestoes > 0
            ? round(($acertos / $totalQuestoes) * (float) $prova->nota_maxima, 1)
            : 0.0;

        $statusAprovacao = $notaFinal >= (self::PERCENTUAL_APROVACAO * (float) $prova->nota_maxima)
            ? 'aprovado'
            : 'recuperacao';

        $dados = [
            'prova_id'         => $prova->id,
            'aluno_id'         => $cartao->aluno_id,
            'turma_id'         => DB::table('alunos')->where('id', $cartao->aluno_id)->value('turma_id'),
            'acertos'          => $acertos,
            'total_questoes'   => $totalQuestoes,
            'nota_final'       => $notaFinal,
            'status_aprovacao' => $statusAprovacao,
            'updated_at'       => now(),
        ];

        $existente = DB::table('notas')->where('cartao_id', $cartao->id)->first();

        if ($existente) {
            DB::table('notas')->where('cartao_id', $cartao->id)->update($dados);
        } else {
            DB::table('notas')->insert(array_merge($dados, [
                'cartao_id'  => $cartao->id,
                'created_at' => now(),
            ]));
        }
    }
}

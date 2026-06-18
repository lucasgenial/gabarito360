<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use Illuminate\Support\Facades\DB;

class CalculadoraNota
{
    /**
     * Busca a nota mínima de aprovação configurada para a rede da escola da prova
     * (RN-009.2 / RN-009.5). Assume escala de 0 a 10; quando a prova usa outra
     * nota_maxima, o limiar é escalado proporcionalmente.
     */
    public static function metaMinima(int $escolaId): float
    {
        $meta = DB::table('escolas')
            ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
            ->join('redes', 'nucleos.rede_id', '=', 'redes.id')
            ->where('escolas.id', $escolaId)
            ->value('redes.meta_minima');

        return $meta !== null ? (float) $meta : 6.0;
    }

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

        $limiarAprovacao = self::metaMinima($prova->escola_id) / 10 * (float) $prova->nota_maxima;
        $statusAprovacao = $notaFinal >= $limiarAprovacao ? 'aprovado' : 'recuperacao';

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

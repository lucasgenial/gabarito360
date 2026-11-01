<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use App\Support\ContextoCorrecao;
use App\Support\Espacos;
use Illuminate\Support\Facades\DB;

class CalculadoraNota
{
    /**
     * Busca a nota mínima de aprovação configurada para a rede da escola da prova
     * (RN-009.2 / RN-009.5). Assume escala de 0 a 10; quando a prova usa outra
     * nota_maxima, o limiar é escalado proporcionalmente.
     */
    public static function metaMinima(?int $escolaId): float
    {
        if ($escolaId === null) return 6.0;
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
    public static function calcular(Cartao $cartao, ?int $atorId = null): void
    {
        if (Espacos::ativo()) abort_unless(ContextoCorrecao::cartaoValido($cartao), 409, 'Relações do cartão divergentes.');
        if (!$cartao->aluno_id) {
            return;
        }

        $prova = $cartao->prova;

        ContextoCorrecao::exigirFilhos('gabaritos', 'prova_id', $prova->id, $cartao->espaco_id);
        $gabarito = ContextoCorrecao::filtrar(DB::table('gabaritos'), $cartao->espaco_id)->where('prova_id', $prova->id)->first();
        if (!$gabarito) {
            return;
        }

        if (Espacos::ativo()) {
            foreach (['gabarito_questoes' => ['gabarito_id', $gabarito->id], 'cartao_respostas' => ['cartao_id', $cartao->id], 'notas' => ['cartao_id', $cartao->id]] as $tabela => [$coluna, $id]) {
                ContextoCorrecao::exigirFilhos($tabela, $coluna, $id, $cartao->espaco_id);
            }
        }

        $questoesGabarito = ContextoCorrecao::filtrar(DB::table('gabarito_questoes'), $cartao->espaco_id)
            ->where('gabarito_id', $gabarito->id)
            ->get()
            ->keyBy('numero_questao');

        $respostas = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)
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
            // Prova com opção de língua: quem marcou Espanhol usa o gabarito alternativo.
            $esperada = ($prova->opcao_lingua && $cartao->lingua === 'ESP' && !empty($questao->alternativa_espanhol))
                ? $questao->alternativa_espanhol
                : $questao->alternativa;
            if ($resposta && $resposta->alternativa === $esperada) {
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
            'turma_id'         => $cartao->turma_aplicacao_id ?? DB::table('alunos')->where('id', $cartao->aluno_id)->value('turma_id'),
            'acertos'          => $acertos,
            'total_questoes'   => $totalQuestoes,
            'nota_final'       => $notaFinal,
            'status_aprovacao' => $statusAprovacao,
            'updated_at'       => now(),
        ];

        $existente = ContextoCorrecao::filtrar(DB::table('notas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->first();

        if ($existente) {
            ContextoCorrecao::filtrar(DB::table('notas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->update($dados);
        } else {
            ContextoCorrecao::filtrar(DB::table('notas'), $cartao->espaco_id)->insert(array_merge($dados, [
                'cartao_id'  => $cartao->id,
                'created_at' => now(),
            ], ContextoCorrecao::autoria($cartao->espaco_id, $atorId)));
        }
    }
}

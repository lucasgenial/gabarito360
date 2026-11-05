<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use App\Support\ContextoCorrecao;
use Illuminate\Support\Facades\DB;

class GravadorLeituras
{
    public static function validar(array $leituras, int $n, int $alternativas): void
    {
        $numeros = array_column($leituras, 'numero_questao');
        sort($numeros, SORT_NUMERIC);
        if (count($leituras) !== $n || $numeros !== range(1, $n)) {
            throw new \UnexpectedValueException('Leitura incompleta ou com questões repetidas.');
        }
        foreach ($leituras as $l) {
            if (! array_key_exists('alternativa', $l) || ($l['alternativa'] !== null && ! in_array($l['alternativa'], str_split(substr('ABCDE', 0, $alternativas)), true))
                || ! isset($l['confianca'],$l['ambigua'],$l['multiplas_marcacoes'],$l['alternativas_detectadas']) || ! is_numeric($l['confianca']) || ! is_finite((float) $l['confianca'])
                || $l['confianca'] < 0 || $l['confianca'] > 1 || ! is_array($l['alternativas_detectadas'])) {
                throw new \UnexpectedValueException('Leitura incompatível com a prova.');
            }
        }
    }

    public static function gravar(Cartao $cartao, array $leituras, ?int $atorId): void
    {
        self::validar($leituras, (int) $cartao->prova->num_questoes, (int) $cartao->prova->num_alternativas);
        ContextoCorrecao::exigirFilhos('cartao_respostas', 'cartao_id', $cartao->id, $cartao->espaco_id);
        ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->delete();
        $rows = [];
        $soma = 0;
        $pendente = false;
        foreach ($leituras as $l) {
            $ambigua = $l['ambigua'] || $l['confianca'] < 0.90;
            if ($ambigua && $l['multiplas_marcacoes'] && $cartao->prova->anular_se_todas) {
                $ambigua = false;
                $l['alternativa'] = null;
            }
            $pendente = $pendente || $ambigua;
            $soma += $l['confianca'];
            $rows[] = ['cartao_id' => $cartao->id, 'numero_questao' => $l['numero_questao'],
                'alternativa' => $ambigua ? null : $l['alternativa'], 'confianca' => $l['confianca'],
                'ambigua' => $ambigua, 'alternativas_detectadas' => json_encode($l['alternativas_detectadas'])] + ContextoCorrecao::autoria($cartao->espaco_id, $atorId);
        }
        if ($rows) {
            DB::table('cartao_respostas')->insert($rows);
        }
        $cartao->status = $pendente ? 'ambiguo' : 'lido';
        $cartao->confianca_geral = count($leituras) ? $soma / count($leituras) : 0;
        $cartao->save();
        if (! $pendente) {
            CalculadoraNota::calcular($cartao, $atorId);
        }
    }
}

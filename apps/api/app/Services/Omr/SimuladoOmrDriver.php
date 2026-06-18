<?php

namespace App\Services\Omr;

/**
 * Implementação inicial do motor OMR.
 *
 * Não realiza visão computacional real (sem leitura de marcadores ArUco ou
 * correção de perspectiva, conforme especificado para o motor definitivo).
 * Gera leituras plausíveis de forma determinística a partir do caminho da
 * imagem, permitindo validar todo o fluxo de upload, processamento,
 * resolução de ambiguidade e cálculo de nota. Pode ser substituída por um
 * driver real sem alterar o restante da aplicação, bastando implementar
 * OmrDriverInterface.
 */
class SimuladoOmrDriver implements OmrDriverInterface
{
    public function processar(string $imagemPath, int $numQuestoes, int $numAlternativas): array
    {
        $opcoes = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $numAlternativas);
        $leituras = [];

        $seedAnterior = mt_rand();

        for ($numero = 1; $numero <= $numQuestoes; $numero++) {
            mt_srand(crc32($imagemPath . '#' . $numero));

            $roll = mt_rand(1, 100);

            if ($roll <= 7) {
                $leituras[] = [
                    'numero_questao'          => $numero,
                    'alternativa'             => null,
                    'confianca'               => mt_rand(950, 999) / 1000,
                    'ambigua'                 => false,
                    'multiplas_marcacoes'     => false,
                    'alternativas_detectadas' => [],
                ];
                continue;
            }

            if ($roll <= 15) {
                $marcadas = $opcoes;
                shuffle($marcadas);
                $marcadas = array_slice($marcadas, 0, 2);

                $leituras[] = [
                    'numero_questao'      => $numero,
                    'alternativa'         => null,
                    'confianca'           => mt_rand(400, 750) / 1000,
                    'ambigua'             => true,
                    'multiplas_marcacoes' => true,
                    'alternativas_detectadas' => array_map(
                        fn ($o) => ['alternativa' => $o, 'confianca' => mt_rand(400, 600) / 1000],
                        $marcadas
                    ),
                ];
                continue;
            }

            $alternativa = $opcoes[mt_rand(0, $numAlternativas - 1)];
            $confianca   = mt_rand(900, 999) / 1000;

            $leituras[] = [
                'numero_questao'          => $numero,
                'alternativa'             => $alternativa,
                'confianca'               => $confianca,
                'ambigua'                 => false,
                'multiplas_marcacoes'     => false,
                'alternativas_detectadas' => [['alternativa' => $alternativa, 'confianca' => $confianca]],
            ];
        }

        mt_srand($seedAnterior);

        return $leituras;
    }
}

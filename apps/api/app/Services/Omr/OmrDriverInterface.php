<?php

namespace App\Services\Omr;

interface OmrDriverInterface
{
    /**
     * Processa a imagem de um cartão-resposta e retorna a leitura de cada questão.
     *
     * Cada item do array de retorno tem o formato:
     * [
     *   'numero_questao'          => int,
     *   'alternativa'             => string|null,  // null quando em branco ou ambígua
     *   'confianca'               => float,         // 0.0 a 1.0
     *   'ambigua'                 => bool,           // múltiplas marcações ou confiança baixa
     *   'multiplas_marcacoes'     => bool,           // true quando mais de uma bolha foi detectada marcada
     *   'alternativas_detectadas' => array,          // [['alternativa' => 'A', 'confianca' => 0.92], ...]
     * ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function processar(string $imagemPath, int $numQuestoes, int $numAlternativas): array;
}

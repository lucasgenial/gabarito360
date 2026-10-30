<?php

namespace App\Services\Omr;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Arquivo: apps/api/app/Services/Omr/NativoOmrDriver.php
 * Função: Motor OMR real. Executa omr/ler_cartao.py (OpenCV) sobre a foto do
 *         cartão nativo: marcadores de canto + correção de perspectiva + QR +
 *         medição de preenchimento das bolinhas.
 */
class NativoOmrDriver implements OmrDriverInterface, OmrIdentificacaoInterface
{
    private array $ultima = [];

    public function processar(string $imagemPath, int $numQuestoes, int $numAlternativas): array
    {
        $this->ultima = [];

        $resultado = Process::path(config('omr.scripts'))
            ->timeout(config('omr.timeout'))
            ->run([config('omr.python'), 'ler_cartao.py', $imagemPath, (string) $numQuestoes, (string) $numAlternativas]);

        $json = json_decode(trim($resultado->output()), true);

        if (!is_array($json) || empty($json['ok'])) {
            $erro = $json['erro'] ?? trim($resultado->errorOutput()) ?: 'falha desconhecida no OMR';
            Log::warning('OMR nativo: leitura falhou', ['imagem' => $imagemPath, 'erro' => $erro]);
            $this->ultima = ['erro' => $erro];

            // Sem leitura possível: todas as questões vão para revisão manual.
            return array_map(fn ($n) => [
                'numero_questao'          => $n,
                'alternativa'             => null,
                'confianca'               => 0.0,
                'ambigua'                 => true,
                'multiplas_marcacoes'     => false,
                'alternativas_detectadas' => [],
            ], range(1, $numQuestoes));
        }

        $this->ultima = [
            'prova_id' => $json['prova_id'] ?? null,
            'aluno_id' => $json['aluno_id'] ?? null,
            'lingua'   => in_array($json['lingua'] ?? null, ['ING', 'ESP'], true) ? $json['lingua'] : null,
            'erro'     => null,
        ];

        // Garante exatamente as questões da prova (o QR pode descrever outro layout)
        $porNumero = collect($json['leituras'])->keyBy('numero_questao');

        return array_map(fn ($n) => $porNumero->get($n) ?? [
            'numero_questao'          => $n,
            'alternativa'             => null,
            'confianca'               => 0.0,
            'ambigua'                 => true,
            'multiplas_marcacoes'     => false,
            'alternativas_detectadas' => [],
        ], range(1, $numQuestoes));
    }

    public function identificacao(): array
    {
        return $this->ultima;
    }
}

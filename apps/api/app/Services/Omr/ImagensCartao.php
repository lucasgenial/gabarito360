<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Arquivo: apps/api/app/Services/Omr/ImagensCartao.php
 * Função: Versões leves da foto do cartão (omr/gerar_imagens.py) e links temporários
 *         assinados para o navegador buscá-las direto na API, sem passar pela web.
 */
class ImagensCartao
{
    public const TIPOS = ['miniatura', 'endireitada', 'original'];

    private const PASTA = 'cartoes/derivados';

    public static function caminho(int $cartaoId, string $tipo): string
    {
        return self::PASTA . "/{$cartaoId}-{$tipo}.jpg";
    }

    /**
     * Faixa da linha de uma questão na foto endireitada, em frações da folha.
     * Mesma geometria de omr/layout.py (grade e bolinhas).
     */
    public static function recorte(int $questao, int $numQuestoes, int $numAlternativas): array
    {
        if ($numQuestoes <= 45) {
            $colunas = 3;
            $linhas  = max(1, (int) ceil($numQuestoes / $colunas));
        } else {
            $linhas  = 20;
            $colunas = (int) ceil($numQuestoes / $linhas);
            if ($colunas > 6) {
                $colunas = 6;
                $linhas  = (int) ceil($numQuestoes / $colunas);
            }
        }

        $larguraColuna = 170.0 / max($colunas, 1);
        $passo      = min(8.0, ($larguraColuna - 12.0) / $numAlternativas);
        $passoLinha = min(8.0, 128.0 / max($linhas - 1, 1));

        $coluna = (int) floor(($questao - 1) / $linhas);
        $linha  = ($questao - 1) % $linhas;
        $y      = 116.0 + $linha * $passoLinha;
        $xFinal = 20.0 + ($coluna + 1) * $larguraColuna - 3.0 - $passo / 2;

        // Inclui o número impresso à esquerda da primeira bolinha
        $x0 = $xFinal - ($numAlternativas - 1) * $passo - 11.0;
        $x1 = $xFinal + 5.0;
        $y0 = $y - 5.0;
        $y1 = $y + 5.0;

        return [
            'x'       => round($x0 / 210, 5),
            'y'       => round($y0 / 297, 5),
            'largura' => round(($x1 - $x0) / 210, 5),
            'altura'  => round(($y1 - $y0) / 297, 5),
        ];
    }

    /** Falha aqui nunca derruba a leitura: sem a versão leve, a tela usa a foto original. */
    public function gerar(Cartao $cartao): bool
    {
        if (!$cartao->imagem_url) {
            return false;
        }

        $disco = Storage::disk('public');

        try {
            $disco->makeDirectory(self::PASTA);
            $resultado = Process::path(config('omr.scripts'))
                ->timeout(config('omr.timeout'))
                ->run([
                    config('omr.python'), 'gerar_imagens.py',
                    $disco->path($cartao->imagem_url),
                    $disco->path(self::caminho($cartao->id, 'miniatura')),
                    $disco->path(self::caminho($cartao->id, 'endireitada')),
                ]);
            $json = json_decode(trim($resultado->output()), true);
        } catch (Throwable $e) {
            $json = ['erro' => $e->getMessage()];
        }

        if (empty($json['ok'])) {
            Log::warning('Imagens do cartão não geradas', ['cartao' => $cartao->id, 'erro' => $json['erro'] ?? 'sem resposta']);
            return false;
        }

        return true;
    }

    public function apagar(int $cartaoId): void
    {
        Storage::disk('public')->delete([
            self::caminho($cartaoId, 'miniatura'),
            self::caminho($cartaoId, 'endireitada'),
        ]);
    }

    /** A validade fecha em hora cheia: o link fica igual entre consultas e o navegador reaproveita o cache. */
    public function url(int $cartaoId, string $tipo): string
    {
        return URL::temporarySignedRoute(
            'cartoes.arquivo',
            now()->startOfHour()->addHours(2),
            ['id' => $cartaoId, 'tipo' => $tipo],
            false
        );
    }
}

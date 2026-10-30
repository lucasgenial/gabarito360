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

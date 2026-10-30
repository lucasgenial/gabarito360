<?php

namespace App\Console\Commands;

use App\Models\Cartao;
use App\Services\Omr\ImagensCartao;
use Illuminate\Console\Command;

/**
 * Arquivo: apps/api/app/Console/Commands/GerarImagensCartoes.php
 * Função: Gera miniatura e foto endireitada dos cartões já lidos. Idempotente: não mexe
 *         em leituras nem notas e pode ser repetido.
 */
class GerarImagensCartoes extends Command
{
    protected $signature = 'g360:cartoes-imagens {--prova= : Só os cartões desta prova}';

    protected $description = 'Gera as versões leves das fotos dos cartões';

    public function handle(ImagensCartao $imagens): int
    {
        $ok = $falhas = 0;

        Cartao::whereNotNull('imagem_url')
            ->when($this->option('prova'), fn ($q, $prova) => $q->where('prova_id', $prova))
            ->each(function (Cartao $cartao) use ($imagens, &$ok, &$falhas) {
                $imagens->gerar($cartao) ? $ok++ : $falhas++;
            });

        $this->info("Cartões: {$ok} gerados, {$falhas} com falha.");

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}

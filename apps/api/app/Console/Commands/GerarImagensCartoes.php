<?php

namespace App\Console\Commands;

use App\Models\Cartao;
use App\Support\Espacos;
use App\Support\ContextoCorrecao;
use App\Services\Omr\ImagensCartao;
use Illuminate\Console\Command;

/**
 * Arquivo: apps/api/app/Console/Commands/GerarImagensCartoes.php
 * Função: Gera miniatura e foto endireitada dos cartões já lidos. Idempotente: não mexe
 *         em leituras nem notas e pode ser repetido.
 */
class GerarImagensCartoes extends Command
{
    protected $signature = 'g360:cartoes-imagens {--prova= : Só os cartões desta prova} {--espaco=} {--aplicar}';

    protected $description = 'Gera as versões leves das fotos dos cartões';

    public function handle(ImagensCartao $imagens): int
    {
        if (Espacos::ativo() && !filter_var($this->option('espaco'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $this->error('Informe o espaço autorizado da execução.');
            return self::FAILURE;
        }
        $ok = $falhas = 0;

        Cartao::whereNotNull('imagem_url')->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $this->option('espaco')))
            ->when($this->option('prova'), fn ($q, $prova) => $q->where('prova_id', $prova))
            ->each(function (Cartao $cartao) use ($imagens, &$ok, &$falhas) {
                if (Espacos::ativo() && !ContextoCorrecao::cartaoValido($cartao)) { $falhas++; return; }
                if (Espacos::ativo() && !$this->option('aplicar')) { $ok++; return; }
                $imagens->gerar($cartao) ? $ok++ : $falhas++;
            });

        $this->info((Espacos::ativo() && !$this->option('aplicar') ? 'Simulação. ' : '')."Cartões: {$ok} válidos, {$falhas} com falha.");

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}

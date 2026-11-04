<?php

namespace App\Console\Commands;

use App\Services\PrepararIdentidadesProvisorias as Preparador;
use Illuminate\Console\Command;

final class PrepararIdentidadesProvisorias extends Command
{
    protected $signature = 'g360:identidades-provisorias {--aplicar : Exige habilitação explícita; padrão é somente simulação} {--lote=100 : Máximo por execução, de 1 a 1000}';
    protected $description = 'Prepara referências provisórias retomáveis sem unir pessoas nem modificar o legado.';

    public function handle(Preparador $preparador): int
    {
        try {
            $lote = filter_var($this->option('lote'), FILTER_VALIDATE_INT);
            if ($lote === false) throw new \InvalidArgumentException();
            $r = $preparador->executar((bool) $this->option('aplicar'), $lote);
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Preparação interrompida. Confira habilitação, esquema e integridade. Unidades confirmadas são preservadas; retome após corrigir a falha.');
            return self::FAILURE;
        }
    }
}

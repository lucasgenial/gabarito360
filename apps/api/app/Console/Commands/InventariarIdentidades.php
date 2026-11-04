<?php

namespace App\Console\Commands;

use App\Services\InventarioIdentidades;
use Illuminate\Console\Command;

final class InventariarIdentidades extends Command
{
    protected $signature = 'g360:identidades-inventariar {--limite=100000 : Máximo de registros, até 100000}';
    protected $description = 'Diagnostica documentos do legado em contagens agregadas, sem escrita ou identificação de pessoas.';

    public function handle(InventarioIdentidades $inventario): int
    {
        try {
            $limite = filter_var($this->option('limite'), FILTER_VALIDATE_INT);
            if ($limite === false) throw new \InvalidArgumentException();
            $r = $inventario->executar($limite);
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Inventário indisponível. Confira configuração, esquema e limite; nenhuma escrita foi executada.');
            return self::FAILURE;
        }
    }
}

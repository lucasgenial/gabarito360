<?php

namespace App\Console\Commands;

use App\Services\InventarioEspacos;
use Illuminate\Console\Command;
use Throwable;

class InventariarEspacos extends Command
{
    protected $signature = 'g360:inventariar-espacos';

    protected $description = 'Diagnóstico T2-A somente de leitura, com IDs institucionais e contagens, sem dados pessoais.';

    public function handle(InventarioEspacos $inventario): int
    {
        try {
            $resultado = $inventario->gerar();
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return $resultado['ocorrencias'] > 0 ? 2 : self::SUCCESS;
        } catch (Throwable $e) {
            // Não exibir SQL, bindings ou mensagem potencialmente sensível.
            $this->error('Não foi possível concluir o inventário. Nenhuma migração foi executada. Confira conexão e versão do esquema.');
            return self::FAILURE;
        }
    }
}

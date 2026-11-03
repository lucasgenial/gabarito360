<?php

namespace App\Console\Commands;

use App\Services\ConsultarRelacoesCatalogo;
use Illuminate\Console\Command;

class EstadoRelacoesCatalogo extends Command
{
    protected $signature = 'g360:catalogo-relacoes-status {--lacunas} {--page=1}';
    protected $description = 'Consulta cobertura e lacunas públicas das fontes, sem alterar protocolos ou instituições';

    public function handle(ConsultarRelacoesCatalogo $consulta): int
    {
        try {
            if (!preg_match('/^[1-9][0-9]*$/D',(string)$this->option('page'))) throw new \RuntimeException('Página inválida.');
            $r = $this->option('lacunas') ? $consulta->lacunas((int)$this->option('page'),50) : $consulta->cobertura();
            $this->line(json_encode($r,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\InventariarVinculosLegados;
use Illuminate\Console\Command;

class InventariarVinculos extends Command
{
    protected $signature = 'g360:inventariar-vinculos';
    protected $description = 'Diagnóstico V1 somente leitura, sem ativar vínculos ou copiar permissões.';

    public function handle(InventariarVinculosLegados $servico): int
    {
        $r = $servico->executar();
        $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $r['usuarios_com_pendencias'] || $r['escopos_sem_usuario'] ? self::FAILURE : self::SUCCESS;
    }
}

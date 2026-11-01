<?php

namespace App\Console\Commands;

use App\Services\RestringirEspacos;
use App\Support\Espacos;
use Illuminate\Console\Command;

class VerificarCorteEspacos extends Command
{
    protected $signature = 'g360:verificar-corte-espacos';
    protected $description = 'Confere adaptador e esquema restritivo antes da abertura; não altera dados.';

    public function handle(RestringirEspacos $servico): int
    {
        try {
            if (!Espacos::ativo() || !$servico->esquemaConcluido()) throw new \RuntimeException('Corte pendente.');
            $this->info('Adaptador ativo e esquema do corte completo. Confira também auditoria e validação funcional.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Corte não confirmado. Mantenha manutenção; confira flag, expansão e restrições.');
            return self::FAILURE;
        }
    }
}

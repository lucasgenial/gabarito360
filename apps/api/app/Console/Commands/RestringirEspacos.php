<?php

namespace App\Console\Commands;

use App\Services\RestringirEspacos as Servico;
use App\Support\EstruturaEspacos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RestringirEspacos extends Command
{
    protected $signature = 'g360:restringir-espacos {--aplicar} {--manifesto=} {--escritores-parados}';
    protected $description = 'Simula DDL T2-E; aplicação exige corte operacional autorizado e manutenção.';

    public function handle(Servico $servico): int
    {
        $aplicar = (bool) $this->option('aplicar');
        if ($aplicar && ($this->option('manifesto') !== EstruturaEspacos::VERSAO || !$this->option('escritores-parados'))) {
            $this->error('Aplicação exige manifesto exato e confirmação operacional de escritores parados.');
            return self::FAILURE;
        }
        // Compartilha a exclusão com preenchimento/conciliação. GET_LOCK protege DDL sem TTL.
        $lock = Cache::lock('g360:t2:preenchimento', 3600);
        try {
            if (!$lock->get()) throw new \RuntimeException('Operação T2 em andamento.');
            $r = $servico->executar($aplicar, fn ($p) => $this->line(json_encode($p, JSON_THROW_ON_ERROR)));
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Apenas mensagens técnicas controladas; nunca a SQL/valores de QueryException.
            if (get_class($e) === \RuntimeException::class) $this->error($e->getMessage());
            $this->error('Corte não concluído. Mantenha manutenção e escritores parados; confira auditoria, manifesto, esquema e último passo. DDL já confirmado não foi revertido.');
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}

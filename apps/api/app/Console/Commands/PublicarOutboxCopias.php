<?php

namespace App\Console\Commands;

use App\Services\OutboxCopia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublicarOutboxCopias extends Command
{
    protected $signature = 'g360:copias-auditoria {--limite=100} {--status : Apenas contagens de pendências}';
    protected $description = 'Publica recibos duráveis de cópias, sem executar cópias.';
    public function handle(OutboxCopia $outbox): int
    {
        try {
            $limite = (string)$this->option('limite');
            if (!preg_match('/^[1-9][0-9]{0,2}$/D',$limite) || (int)$limite > 100) return self::FAILURE;
            $resultado = $this->option('status') ? [] : $outbox->publicar((int)$limite);
            $pendentes = DB::table('copias_outbox')->whereNull('entregue_em');
            $this->line(json_encode($resultado + ['entrega_ativa'=>(bool)config('copias.auditoria_entrega'),
                'pendentes'=>(clone $pendentes)->count(), 'com_falha'=>(clone $pendentes)->whereNotNull('erro_tecnico')->count()],JSON_THROW_ON_ERROR));
            return ($resultado['falhas'] ?? 0) ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) { $this->error('Recibos de cópias indisponíveis; nenhuma cópia foi executada.'); return self::FAILURE; }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\AuditarIntegridadeEspacos;
use Illuminate\Console\Command;

class AuditarEspacos extends Command
{
    protected $signature = 'g360:auditar-espacos';
    protected $description = 'Audita cobertura, autoria, unicidades e relações T2 sem escrita nem dados pessoais.';

    public function handle(AuditarIntegridadeEspacos $servico): int
    {
        try {
            $r = $servico->executar();
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return $r['apto_dados'] ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Auditoria não concluída; confira a expansão e o esquema. Nenhuma escrita executada.');
            return self::FAILURE;
        }
    }
}

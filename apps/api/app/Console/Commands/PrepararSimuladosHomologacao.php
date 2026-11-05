<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimuladosHomologacao;

/** Operação pontual de desenvolvimento; não disponibiliza reclassificação por HTTP. */
final class PrepararSimuladosHomologacao extends Command
{
    protected $signature = 'g360:preparar-simulados-homologacao {--aplicar : Gravar a adaptação autorizada, após backup e ensaio}';
    protected $description = 'Confere/prepara os quatro simulados e professores fictícios exclusivos da homologação';

    public function handle(SimuladosHomologacao $servico): int
    {
        try {
            $this->line(json_encode($servico->executar((bool)$this->option('aplicar')), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Não imprimir exceções SQL, bindings, documentos, credenciais ou dados pessoais.
            report($e);
            $this->error('Adaptação recusada. Confira o ambiente e o diagnóstico protegido; nenhuma gravação parcial foi confirmada.');
            return self::FAILURE;
        }
    }
}

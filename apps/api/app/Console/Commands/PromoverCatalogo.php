<?php

namespace App\Console\Commands;

use App\Services\PublicarCatalogoInstituicoes;
use Illuminate\Console\Command;
use RuntimeException;

class PromoverCatalogo extends Command
{
    protected $signature = 'g360:catalogo-promover {importacao} {--ufs=} {--dry-run} {--lote=1000} {--max-lotes=} {--substituir=} {--motivo=}';
    protected $description = 'Prepara projeção indexada e ativa edição completa do catálogo, sem alterar escolas';

    public function handle(PublicarCatalogoInstituicoes $servico): int
    {
        try {
            foreach (['lote','max-lotes'] as $opcao) if ($this->option($opcao)!==null && !preg_match('/^[1-9][0-9]*$/D',(string)$this->option($opcao))) throw new RuntimeException('Limites de lote inválidos.');
            $r = $servico->executar($this->argument('importacao'),(string)$this->option('ufs'),(int)$this->option('lote'),
                $this->option('max-lotes')!==null ? (int)$this->option('max-lotes') : null,$this->option('substituir'),$this->option('motivo'),(bool)$this->option('dry-run'));
            $this->line(json_encode($r,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\PrepararRelacoesCatalogo;
use Illuminate\Console\Command;
use RuntimeException;

class RelacionarCatalogo extends Command
{
    protected $signature = 'g360:catalogo-relacionar {arquivo} {--basica=} {--superior=} {--sha256=} {--dry-run} {--lote=1000} {--max-lotes=}';
    protected $description = 'Registra relações públicas da edição INEP, sem criar instituições, campi ou vínculos pessoais';

    public function handle(PrepararRelacoesCatalogo $servico): int
    {
        try {
            foreach (['basica','superior','sha256'] as $opcao) if (!$this->option($opcao)) throw new RuntimeException('Informe edições básica/superior e SHA-256 esperado.');
            foreach (['lote','max-lotes'] as $opcao) if ($this->option($opcao)!==null && !preg_match('/^[1-9][0-9]*$/D',(string)$this->option($opcao))) throw new RuntimeException('Limites de lote inválidos.');
            $r = $servico->executar($this->argument('arquivo'),$this->option('basica'),$this->option('superior'),$this->option('sha256'),
                (int)$this->option('lote'),$this->option('max-lotes')!==null ? (int)$this->option('max-lotes') : null,(bool)$this->option('dry-run'));
            $this->line(json_encode($r,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}

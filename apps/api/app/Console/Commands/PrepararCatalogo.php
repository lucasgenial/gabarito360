<?php

namespace App\Console\Commands;

use App\Services\PrepararCatalogoInstituicoes;
use Illuminate\Console\Command;
use RuntimeException;

class PrepararCatalogo extends Command
{
    protected $signature = 'g360:catalogo-preparar {arquivo} {--fonte=basica} {--ano=2025} {--ufs=} {--municipios=} {--encoding=Windows-1252} {--sha256=} {--todas-ufs} {--dry-run} {--lote=1000} {--max-lotes=}';
    protected $description = 'Prepara observações públicas nacionais em lotes retomáveis, sem publicar catálogo';

    public function handle(PrepararCatalogoInstituicoes $servico): int
    {
        try {
            if (!$this->option('todas-ufs') || !preg_match('/^[a-f0-9]{64}$/Di', (string)$this->option('sha256'))) throw new RuntimeException('Exige --todas-ufs e --sha256 esperado.');
            foreach (['lote','max-lotes'] as $opcao) {
                $valor = $this->option($opcao);
                if ($valor !== null && !preg_match('/^[1-9][0-9]*$/D', (string)$valor)) throw new RuntimeException('Limites de lote inválidos.');
            }
            $recibo = $servico->executar($this->argument('arquivo'),$this->option('fonte'),(int)$this->option('ano'),
                (string)$this->option('ufs'),(string)$this->option('municipios'),strtolower($this->option('sha256')),
                $this->option('encoding'),(int)$this->option('lote'),$this->option('max-lotes') !== null ? (int)$this->option('max-lotes') : null,
                (bool)$this->option('dry-run'));
            $this->line(json_encode($recibo, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}

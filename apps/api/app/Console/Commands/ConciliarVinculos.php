<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConciliarVinculos extends Command
{
    protected $signature = 'g360:conciliar-vinculos {manifesto?} {--preparar} {--sha256=}';
    protected $description = 'Confere origem, vínculos e contas adiadas; somente leitura, nunca ativa o regime.';

    public function handle(\App\Services\ConciliarVinculos $servico): int
    {
        try {
            if ($this->option('preparar')) {
                if ($this->argument('manifesto') || $this->option('sha256')) throw new \RuntimeException('Use --preparar isoladamente.');
                $r = $servico->preparar();
            } else {
                $path = $this->argument('manifesto');
                if (!$path || !is_file($path) || !is_readable($path) || filesize($path) > 10 * 1024 * 1024) throw new \RuntimeException('Informe manifesto local de até 10 MiB.');
                $bytes = file_get_contents($path);
                if (!is_string($this->option('sha256')) || !hash_equals(hash('sha256', $bytes), $this->option('sha256'))) throw new \RuntimeException('Informe o hash exato do arquivo revisado.');
                $r = $servico->conferir(json_decode($bytes, true, 512, JSON_THROW_ON_ERROR));
                $r['arquivo_sha256'] = hash('sha256', $bytes);
            }
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return ($r['conciliacao_aprovavel'] ?? true) ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e instanceof \RuntimeException && !$e instanceof \Illuminate\Database\QueryException ? $e->getMessage() : 'Conciliação recusada; confira esquema e manifesto. Nenhuma alteração foi realizada.');
            return self::FAILURE;
        }
    }
}

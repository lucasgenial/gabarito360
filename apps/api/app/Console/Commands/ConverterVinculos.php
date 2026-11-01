<?php

namespace App\Console\Commands;

use App\Services\ConverterVinculosLegados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ConverterVinculos extends Command
{
    protected $signature = 'g360:converter-vinculos {manifesto?} {--preparar} {--aplicar} {--sha256=} {--ator=}';
    protected $description = 'Prepara rascunho ou simula manifesto V1-B; aplicação explícita exige hash, executor e manutenção.';

    public function handle(ConverterVinculosLegados $servico): int
    {
        $lock = Cache::lock('g360:v1:conversao', 3600);
        $adquirido = false;
        try {
            if ($this->option('preparar')) {
                if ($this->argument('manifesto') || $this->option('aplicar') || $this->option('sha256') || $this->option('ator')) throw new \RuntimeException('Use --preparar isoladamente.');
                $r = $servico->preparar();
            } else {
                $path = $this->argument('manifesto');
                if (!$path || !is_file($path) || !is_readable($path) || filesize($path) > 10 * 1024 * 1024) throw new \RuntimeException('Informe um manifesto JSON local de até 10 MiB.');
                $bytes = file_get_contents($path);
                $aplicar = (bool) $this->option('aplicar');
                $sha = $this->option('sha256');
                if (($aplicar || $sha) && (!is_string($sha) || !hash_equals(hash('sha256', $bytes), $sha))) throw new \RuntimeException('Hash do arquivo não corresponde à aprovação.');
                $ator = $this->option('ator');
                if ($aplicar && (!is_string($ator) || !ctype_digit($ator) || (int) $ator < 1)) throw new \RuntimeException('Informe --ator com o ID do administrador executor.');
                $m = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($m)) throw new \RuntimeException('Manifesto deve ser objeto JSON.');
                $adquirido = $lock->get();
                if (!$adquirido) throw new \RuntimeException('Há outra conversão em execução.');
                $r = $servico->executar($m, $aplicar, $aplicar ? (int) $ator : null);
                $r['arquivo_sha256'] = hash('sha256', $bytes);
            }
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (Throwable $e) {
            // Não imprimir SQL, conteúdos do manifesto ou dados de pessoas em erros de infraestrutura.
            $this->error($e instanceof \RuntimeException && !$e instanceof \Illuminate\Database\QueryException
                ? $e->getMessage() : 'Conversão recusada; confira formato, esquema e conciliação. Nenhuma alteração desta transação foi confirmada.');
            return self::FAILURE;
        } finally {
            if ($adquirido) $lock->release();
        }
    }
}

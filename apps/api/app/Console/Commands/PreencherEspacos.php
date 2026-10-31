<?php

namespace App\Console\Commands;

use App\Services\PreencherEspacosLegados;
use App\Support\EstruturaEspacos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PreencherEspacos extends Command
{
    protected $signature = 'g360:preencher-espacos {--aplicar} {--manifesto=}';
    protected $description = 'Simula o manifesto T2; aplicação explícita exige manutenção e aprovação operacional.';

    public function handle(PreencherEspacosLegados $servico): int
    {
        $aplicar = (bool) $this->option('aplicar');
        if ($aplicar && $this->option('manifesto') !== EstruturaEspacos::VERSAO) {
            $this->error('Informe o identificador exato do manifesto aprovado.');
            return self::FAILURE;
        }
        $lock = Cache::lock('g360:t2:preenchimento', 3600);
        try {
            if (!$lock->get()) {
                $this->error('Já existe uma execução de preenchimento.');
                return self::FAILURE;
            }
            $r = $servico->executar($aplicar);
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Preenchimento não concluído. Confira manutenção, esquema, manifesto e inventário; nenhuma alteração parcial desta transação foi confirmada.');
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}

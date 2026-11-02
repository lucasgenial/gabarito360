<?php
namespace App\Console\Commands;

use App\Services\FilaExecucaoCopia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublicarExecucoesCopias extends Command
{
    protected $signature='g360:copias-executar {--status : Apenas contagens}';
    protected $description='Publica intenções de execução de materiais revisados.';
    public function handle(FilaExecucaoCopia $fila): int
    {
        try {
            $r=$this->option('status') ? [] : $fila->publicar();
            $r['estados']=DB::table('copias_execucoes')->selectRaw('estado, COUNT(*) as quantidade')->groupBy('estado')->pluck('quantidade','estado')->all();
            $this->line(json_encode($r,JSON_THROW_ON_ERROR));
            return ($r['falhas'] ?? 0) ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) { $this->error('Execução indisponível; intenções permanecem pendentes.'); return self::FAILURE; }
    }
}

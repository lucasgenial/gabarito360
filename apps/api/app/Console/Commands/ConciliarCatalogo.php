<?php

namespace App\Console\Commands;

use App\Services\ConsultarCatalogoInstituicoes;
use Illuminate\Console\Command;

class ConciliarCatalogo extends Command
{
    protected $signature = 'g360:catalogo-conciliar';
    protected $description = 'Relatório de candidatos institucionais por INEP/geografia, sem alterar escolas';

    public function handle(ConsultarCatalogoInstituicoes $consulta): int
    {
        $this->line(json_encode(['modo'=>'relatorio_sem_associacao','escolas'=>$consulta->conciliacao()],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
        return self::SUCCESS;
    }
}

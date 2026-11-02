<?php
namespace App\Console\Commands;

use App\Services\ArquivoMensalAuditoria;
use Illuminate\Console\Command;

class ArquivarAuditoria extends Command
{
    protected $signature = 'g360:arquivar-auditoria {mes : Mês UTC fechado, AAAA-MM} {destino : Diretório privado novo} {--verificar : Verifica pacote existente sem consultar ou escrever no banco}';
    protected $description = 'Cria ou verifica cópia mensal cifrada, sem excluir eventos.';

    public function handle(ArquivoMensalAuditoria $servico): int
    {
        try {
            $metodo = $this->option('verificar') ? 'verificar' : 'criar';
            $r = $servico->$metodo($this->argument('mes'), $this->argument('destino'));
            $this->line(json_encode($r, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Arquivo indisponível: confira mês fechado, destino, chave e integridade. Nenhum evento foi excluído. Não utilize pacote incompleto.');
            return self::FAILURE;
        }
    }
}

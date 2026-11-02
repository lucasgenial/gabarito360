<?php
namespace App\Console\Commands;

use App\Services\ConferenciaAuditoria;
use Illuminate\Console\Command;

class ConferirAuditoria extends Command
{
    protected $signature = 'g360:conferir-auditoria {--dias= : Simula prazo sem aprovar política ou excluir} {--comparar= : Manifesto anterior para conferir restauração}';
    protected $description = 'Confere inventário, retenção e integridade da auditoria sem escrita.';
    public function handle(ConferenciaAuditoria $servico): int
    {
        try {
            $r=$servico->executar($this->option('dias'));
            if ($arquivo=$this->option('comparar')) {
                if (!is_file($arquivo) || filesize($arquivo)>1048576) throw new \RuntimeException();
                $anterior=json_decode(file_get_contents($arquivo),true,512,JSON_THROW_ON_ERROR);
                $r['restauracao_confere']=$servico->comparar($r,$anterior);
            }
            $this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
            return ($r['restauracao_confere'] ?? true) ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable) {
            $this->error('Conferência indisponível: confira esquema, configuração e manifesto. Nenhuma escrita executada.');
            return self::FAILURE;
        }
    }
}

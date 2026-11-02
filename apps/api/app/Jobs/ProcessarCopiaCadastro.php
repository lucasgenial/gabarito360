<?php
namespace App\Jobs;

use App\Services\LotesCadastroCopia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\{ShouldBeEncrypted,ShouldQueue};

class ProcessarCopiaCadastro implements ShouldQueue,ShouldBeEncrypted
{
    use Queueable;
    public int $tries=3;
    public int $timeout=30;
    public function __construct(public readonly string $loteId) { $this->onQueue('baixa'); }
    public function backoff(): array { return [60,120]; }
    public function handle(): void
    {
        $s=new LotesCadastroCopia;
        try {
            if ($s->executarPasso($this->loteId)==='preparado') $s->executarPasso($this->loteId);
        } catch (\Throwable) {
            try { $s->registrarFalhaExecucao($this->loteId); } catch (\Throwable) {}
            throw new \RuntimeException('Execução de cadastro pendente; consulte o código técnico.');
        }
    }
}

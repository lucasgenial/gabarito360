<?php
namespace App\Jobs;

use App\Services\LotesCopia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\{ShouldBeEncrypted,ShouldQueue};

class ProcessarCopiaMaterial implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;
    public int $tries=3;
    public int $timeout=30;
    public function __construct(public readonly string $loteId) { $this->onQueue('baixa'); }
    public function backoff(): array { return [60,120]; }
    public function handle(): void
    {
        $s=new LotesCopia;
        try {
            // Duas transações separadas: uma preparação e uma publicação, no máximo uma prova.
            if ($s->executarPasso($this->loteId)==='preparado') $s->executarPasso($this->loteId);
        } catch (\Throwable) {
            try { $s->registrarFalhaExecucao($this->loteId); } catch (\Throwable) {}
            // Não gravar SQL com título/gabarito nem exceção anterior em failed_jobs.
            throw new \RuntimeException('Execução de cópia pendente; consulte o código técnico.');
        }
    }
}

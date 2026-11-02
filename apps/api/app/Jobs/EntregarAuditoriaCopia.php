<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\{ShouldBeEncrypted, ShouldQueue};

class EntregarAuditoriaCopia implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;
    public int $tries = 3;
    public int $timeout = 20;
    public function __construct(public readonly string $reciboId) { $this->onQueue('baixa'); }
    public function backoff(): array { return [60,120]; }
    public function handle(): void { app(\App\Services\OutboxCopia::class)->entregar($this->reciboId); }
}

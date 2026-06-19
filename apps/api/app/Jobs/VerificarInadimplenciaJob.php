<?php

namespace App\Jobs;

use App\Models\Assinatura;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class VerificarInadimplenciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $graceDays = config('mercadopago.grace_days', 7);

        // Trial expirado sem pagamento aprovado → atrasada
        $trialExpiradas = Assinatura::where('status', 'trial')
            ->where('trial_termina_em', '<', Carbon::now())
            ->whereDoesntHave('pagamentos', fn ($q) => $q->where('status', 'aprovado'))
            ->get();

        foreach ($trialExpiradas as $assinatura) {
            $assinatura->update(['status' => 'atrasada']);
            Log::info("Assinatura #{$assinatura->id} — trial expirado, acesso suspenso.");
        }

        // Ativa com próxima cobrança ultrapassada além do prazo de carência → atrasada
        $ativasAtrasadas = Assinatura::where('status', 'ativa')
            ->where('proxima_cobranca_em', '<', Carbon::now()->subDays($graceDays))
            ->whereDoesntHave('pagamentos', fn ($q) => $q->where('status', 'aprovado')->where('created_at', '>', Carbon::now()->subDays($graceDays)))
            ->get();

        foreach ($ativasAtrasadas as $assinatura) {
            $assinatura->update(['status' => 'atrasada']);
            Log::info("Assinatura #{$assinatura->id} — inadimplente após carência, acesso suspenso.");
        }
    }
}

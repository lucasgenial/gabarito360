<?php

namespace App\Actions\Integracoes;

use App\Enums\IntegracaoStatus;
use App\Models\Integracao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class DisconnectIntegracaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Integracao $integracao, User $actor): void
    {
        DB::transaction(function () use ($integracao, $actor): void {
            $integracao->credenciais()->delete();
            $integracao->update([
                'status' => IntegracaoStatus::DISCONNECTED,
                'ativa' => false,
            ]);

            $this->audit->record(
                action: AuditAction::INTEGRATION_DISCONNECTED,
                entityType: 'integracao',
                entityId: $integracao->id,
                actorUserId: $actor->id,
                metadata: ['chave' => $integracao->chave],
            );

            $integracao->delete();
        });
    }
}

<?php

namespace App\Actions\Integracoes;

use App\Enums\IntegracaoStatus;
use App\Models\Integracao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;

class TestIntegracaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * Teste de conexão (stub até existirem integrações externas reais):
     * valida a presença de credenciais e atualiza o estado.
     *
     * @return array{status: string, mensagem: string}
     */
    public function execute(Integracao $integracao, User $actor): array
    {
        $temCredenciais = $integracao->credenciais()->exists();

        $resultado = $temCredenciais
            ? ['status' => 'ok', 'mensagem' => 'Conexao validada.']
            : ['status' => 'falha', 'mensagem' => 'Credenciais ausentes ou incompletas.'];

        $integracao->update([
            'ultima_execucao' => now(),
            'status' => $temCredenciais ? IntegracaoStatus::CONNECTED : IntegracaoStatus::ERROR,
            'erros' => $temCredenciais ? null : ['Credenciais ausentes ou incompletas.'],
        ]);

        $this->audit->record(
            action: AuditAction::INTEGRATION_TESTED,
            entityType: 'integracao',
            entityId: $integracao->id,
            actorUserId: $actor->id,
            metadata: ['chave' => $integracao->chave, 'resultado' => $resultado['status']],
        );

        return $resultado;
    }
}

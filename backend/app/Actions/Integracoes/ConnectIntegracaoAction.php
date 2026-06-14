<?php

namespace App\Actions\Integracoes;

use App\Enums\IntegracaoStatus;
use App\Models\Integracao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ConnectIntegracaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * @param  array{escopo: string, nucleo_id: ?string, escola_id: ?string, chave: string, nome?: ?string, descricao?: ?string}  $attributes
     * @param  array<string, string>  $credenciais
     */
    public function execute(array $attributes, array $credenciais, User $actor): Integracao
    {
        return DB::transaction(function () use ($attributes, $credenciais, $actor): Integracao {
            $integracao = Integracao::query()->updateOrCreate(
                [
                    'escopo' => $attributes['escopo'],
                    'nucleo_id' => $attributes['nucleo_id'] ?? null,
                    'escola_id' => $attributes['escola_id'] ?? null,
                    'chave' => $attributes['chave'],
                ],
                [
                    'nome' => $attributes['nome'] ?? $attributes['chave'],
                    'descricao' => $attributes['descricao'] ?? null,
                    'status' => $credenciais === [] ? IntegracaoStatus::PENDING : IntegracaoStatus::CONNECTED,
                    'ativa' => true,
                    'erros' => null,
                ],
            );

            $integracao->credenciais()->delete();

            foreach ($credenciais as $chave => $valor) {
                $integracao->credenciais()->create([
                    'chave' => $chave,
                    'valor_criptografado' => $valor,
                ]);
            }

            // Auditoria registra apenas os NOMES dos campos de credencial, nunca os valores.
            $this->audit->record(
                action: AuditAction::INTEGRATION_CONNECTED,
                entityType: 'integracao',
                entityId: $integracao->id,
                actorUserId: $actor->id,
                metadata: ['chave' => $attributes['chave'], 'campos' => array_keys($credenciais)],
            );

            return $integracao->refresh();
        });
    }
}

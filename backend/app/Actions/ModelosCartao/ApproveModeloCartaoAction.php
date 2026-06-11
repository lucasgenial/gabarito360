<?php

namespace App\Actions\ModelosCartao;

use App\Enums\ModeloCartaoStatus;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\ModelosCartao\ModeloCartaoConfigurationValidator;
use Illuminate\Support\Facades\DB;

class ApproveModeloCartaoAction
{
    public function __construct(
        private AuditService $audit,
        private ModeloCartaoConfigurationValidator $validator,
    ) {}

    public function execute(ModeloCartao $model, User $actor): ModeloCartao
    {
        return DB::transaction(function () use ($model, $actor): ModeloCartao {
            $this->validator->assertValid([
                'quantidade_questoes' => $model->quantidade_questoes,
                'quantidade_alternativas' => $model->quantidade_alternativas,
                'alternativas' => $model->alternativas,
                'tipo_codigo' => $model->tipo_codigo->value,
                'origem_codigo' => $model->origem_codigo->value,
                'configuracao_omr' => $model->configuracao_omr,
                'artefato_checksum_sha256' => $model->artefato_checksum_sha256,
            ], forApproval: true);

            $model->update([
                'status' => ModeloCartaoStatus::APPROVED,
                'homologado_por' => $actor->id,
                'homologado_at' => now(),
            ]);

            $this->audit->record(
                action: AuditAction::CARD_MODEL_APPROVED,
                entityType: 'modelo_cartao',
                entityId: $model->id,
                actorUserId: $actor->id,
                before: ['status' => ModeloCartaoStatus::DRAFT],
                after: [
                    'status' => ModeloCartaoStatus::APPROVED,
                    'artefato_checksum_sha256' => $model->artefato_checksum_sha256,
                ],
                nucleoId: $model->nucleo_id,
            );

            return $model->refresh();
        });
    }
}

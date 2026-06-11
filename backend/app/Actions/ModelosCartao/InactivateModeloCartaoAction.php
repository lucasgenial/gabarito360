<?php

namespace App\Actions\ModelosCartao;

use App\Enums\ModeloCartaoStatus;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InactivateModeloCartaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(ModeloCartao $model, User $actor): ModeloCartao
    {
        return DB::transaction(function () use ($model, $actor): ModeloCartao {
            if ($model->status === ModeloCartaoStatus::INACTIVE) {
                return $model;
            }

            $previousStatus = $model->status;
            $model->update(['status' => ModeloCartaoStatus::INACTIVE]);

            $this->audit->record(
                action: AuditAction::CARD_MODEL_INACTIVATED,
                entityType: 'modelo_cartao',
                entityId: $model->id,
                actorUserId: $actor->id,
                before: ['status' => $previousStatus],
                after: ['status' => ModeloCartaoStatus::INACTIVE],
                nucleoId: $model->nucleo_id,
            );

            return $model->refresh();
        });
    }
}

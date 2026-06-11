<?php

namespace App\Actions\ModelosCartao;

use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateModeloCartaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(ModeloCartao $model, array $attributes, User $actor): ModeloCartao
    {
        return DB::transaction(function () use ($model, $attributes, $actor): ModeloCartao {
            $model->update($attributes);

            if ($model->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::CARD_MODEL_UPDATED,
                    entityType: 'modelo_cartao',
                    entityId: $model->id,
                    actorUserId: $actor->id,
                    metadata: ['campos_alterados' => array_keys($attributes)],
                    nucleoId: $model->nucleo_id,
                );
            }

            return $model->refresh();
        });
    }
}

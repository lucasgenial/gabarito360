<?php

namespace App\Actions\ModelosCartao;

use App\Enums\ModeloCartaoStatus;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateModeloCartaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(ModeloCartao $model, array $attributes, User $actor): ModeloCartao
    {
        return DB::transaction(function () use ($model, $attributes, $actor): ModeloCartao {
            $lockedModel = ModeloCartao::query()->lockForUpdate()->findOrFail($model->id);

            if ($lockedModel->status !== ModeloCartaoStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'modelo_cartao' => ['Somente modelos em rascunho podem ser alterados.'],
                ]);
            }

            $lockedModel->update($attributes);

            if ($lockedModel->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::CARD_MODEL_UPDATED,
                    entityType: 'modelo_cartao',
                    entityId: $lockedModel->id,
                    actorUserId: $actor->id,
                    metadata: ['campos_alterados' => array_keys($attributes)],
                    nucleoId: $lockedModel->nucleo_id,
                );
            }

            return $lockedModel->refresh();
        });
    }
}

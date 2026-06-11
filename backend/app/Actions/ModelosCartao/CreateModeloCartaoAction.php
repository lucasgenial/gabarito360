<?php

namespace App\Actions\ModelosCartao;

use App\Enums\ModeloCartaoStatus;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateModeloCartaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): ModeloCartao
    {
        return DB::transaction(function () use ($attributes, $actor): ModeloCartao {
            $model = ModeloCartao::query()->create([
                ...$attributes,
                'status' => ModeloCartaoStatus::DRAFT,
                'criado_por' => $actor->id,
            ])->refresh();

            $this->audit->record(
                action: AuditAction::CARD_MODEL_CREATED,
                entityType: 'modelo_cartao',
                entityId: $model->id,
                actorUserId: $actor->id,
                after: $this->auditSnapshot($model),
                nucleoId: $model->nucleo_id,
            );

            return $model;
        });
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(ModeloCartao $model): array
    {
        return [
            'nucleo_id' => $model->nucleo_id,
            'nome' => $model->nome,
            'versao' => $model->versao,
            'tipo_codigo' => $model->tipo_codigo,
            'origem_codigo' => $model->origem_codigo,
            'artefato_checksum_sha256' => $model->artefato_checksum_sha256,
            'status' => $model->status,
        ];
    }
}
